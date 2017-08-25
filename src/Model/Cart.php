<?php 

namespace Hcode\Model;

use \Hcode\DB\Sql;
use \Hcode\Model;
use \Hcode\Model\Product;
use \Hcode\Model\User;

class Cart extends Model {

    const ZIPCODE = '09853120';
    const SERVICE = '41106'; 
        /* 
            40010 = SEDEX
            40045 = SEDEX a Cobrar
            40215 = SEDEX 10
            40290 = SEDEX Hoje        
            41106 = PAC        
        */
    const SESSION = 'Cart';
    const SESSION_MSG = 'CartMsg';

    public static function clearSession()
    {

        $_SESSION[Cart::SESSION] = NULL;
        session_regenerate_id();

    }

    public static function getFromSession()
    {

        $cart = new Cart();

        if (isset($_SESSION[Cart::SESSION]) && isset($_SESSION[Cart::SESSION]['idcart'])) {

            $cart->get((int)$_SESSION[Cart::SESSION]['idcart']);

        } else {

            $sql = new Sql();

            $result = $sql->select("SELECT * FROM tb_carts WHERE dessessionid = :dessessionid", [
                ':dessessionid'=>session_id()
            ]);

            if (count($result) > 0 && (int)$result[0]['idcart'] > 0) {

                $cart->setData($result[0]);

            } else {
                
                $data = [
                    'dessessionid'=>session_id()
                ];

                $user = User::getFromSession();

                if (User::checkLogin()) {
                    $data['iduser'] = $user->getiduser();
                }

                $cart->setData($data);

                $cart->save();

                $cart->setToSession();

            }

        }

        return $cart;

    }

    public function setToSession()
    {

        $_SESSION[Cart::SESSION] = $this->getValues();

    }

    public function save()
	{

		$sql = new Sql();

		$results = $sql->select("CALL sp_carts_save(:idcart, :dessessionid, :iduser, :deszipcode, :vlfreight, :nrdays)", array(
			":idcart"=>$this->getidcart(),
			":dessessionid"=>$this->getdessessionid(),
			":iduser"=>$this->getiduser(),
			":deszipcode"=>$this->getdeszipcode(),
			":vlfreight"=>$this->getvlfreight(),
			":nrdays"=>$this->getnrdays()
		));

		$this->setData($results[0]);

	}

    public function get($idcart)
	{

		$sql = new Sql();

		$results = $sql->select("SELECT * FROM tb_carts WHERE idcart = :idcart", array(
			":idcart"=>$idcart
		));

		$this->setData($results[0]);

	}

    public function getCalculateTotal()
    {

        $this->updateFreight();

        $totals = $this->getProductsTotals();

        $this->setvlsubtotal($totals['vlprice']);
        $this->setvltotal($totals['vlprice'] + $this->getvlfreight());

    }

    public function getValues()
    {

        $this->getCalculateTotal();

        $values = parent::getValues();

        return $values;

    }

    public function addProduct(Product $product)
    {

        $sql = new Sql();

        $sql->query("INSERT INTO tb_cartsproducts (idcart, idproduct) VALUES(:idcart, :idproduct)", [
            ':idcart'=>$this->getidcart(),
            ':idproduct'=>$product->getidproduct()
        ]);

        $this->getCalculateTotal();

    }

    public function removeProduct(Product $product, $all = false)
    {

        $sql = new Sql();

        if ($all === true) {

            $sql->query("UPDATE tb_cartsproducts SET dtremoved = NOW() WHERE idcart = :idcart AND idproduct = :idproduct AND dtremoved IS NULL", [
                ':idcart'=>$this->getidcart(),
                ':idproduct'=>$product->getidproduct()
            ]);

        } else {

            $sql->query("UPDATE tb_cartsproducts SET dtremoved = NOW() WHERE idcart = :idcart AND idproduct = :idproduct AND dtremoved IS NULL LIMIT 1", [
                ':idcart'=>$this->getidcart(),
                ':idproduct'=>$product->getidproduct()
            ]);

        }

        $this->getCalculateTotal();

    }

    public function getProducts()
    {

        $sql = new Sql();

        $list = $sql->select("
            SELECT b.idproduct, desproduct, vlprice, vlwidth, vlheight, vllength, vlweight, COUNT(*) AS nrqtd, SUM(vlprice) AS vltotal
            FROM tb_cartsproducts a
            INNER JOIN tb_products b USING(idproduct)
            WHERE a.idcart = :idcart AND dtremoved IS NULL
            GROUP BY b.idproduct, desproduct, vlprice, vlwidth, vlheight, vllength, vlweight
            ORDER BY desproduct
        ", [
            ':idcart'=>$this->getidcart()
        ]);

        return Product::formatProducts($list);

    }

    public function getProductsTotals()
    {

        $sql = new Sql();

        $results = $sql->select("
            SELECT SUM(vlprice) AS vlprice, SUM(vlwidth) AS vlwidth, SUM(vlheight) AS vlheight, SUM(vllength) AS vllength, SUM(vlweight) AS vlweight, COUNT(*) AS nrqtd
            FROM tb_cartsproducts a
            INNER JOIN tb_products b USING(idproduct)
            WHERE a.idcart = :idcart AND dtremoved IS NULL
        ", [
            ':idcart'=>$this->getidcart()
        ]);

        if (count($results) > 0) {
            return $results[0];
        } else {
            return [];
        }        

    }

    public function updateFreight()
    {

        if ($this->getdeszipcode() != '') {

            $this->setFreight($this->getdeszipcode());

        }

    }

    public function setFreight($nrzipcode)
    {

        $nrzipcode = str_replace('-', '', $nrzipcode);

        $totals = $this->getProductsTotals();

        if ($totals['nrqtd'] == 0) {

            Cart::clearCartFreightMessage();

            $this->setvlfreight(NULL);
            $this->setnrdays(NULL);
            $this->setdeszipcode($nrzipcode);

            $this->save();

            return false;
        }

        if ($totals['vllength'] < 16) $totals['vllength'] = 16;
        if ($totals['vlwidth'] < 11) $totals['vlwidth'] = 11;

        $this->xml = simplexml_load_file(
			"http://ws.correios.com.br/calculador/CalcPrecoPrazo.asmx/CalcPrecoPrazo?".
            "nCdEmpresa=&".
            "sDsSenha=&".
            "sCepOrigem=".Cart::ZIPCODE."&".
            "sCepDestino=".$nrzipcode."&".
            "nVlPeso=".$totals['vlweight']."&".
            "nCdFormato=1&".
            "nVlComprimento=".$totals['vllength']."&".
            "nVlAltura=".$totals['vlheight']."&".
            "nVlLargura=".$totals['vlwidth']."&".
            "sCdMaoPropria=S&".
            "nVlValorDeclarado=".$totals['vlprice']."&".
            "sCdAvisoRecebimento=S&".
            "nCdServico=".Cart::SERVICE."&".
            "nVlDiametro=0&".
            "StrRetorno=xml"
        );

		if(!$this->xml->Servicos->cServico){
	        throw new \Exception("Error Processing Request", 400);
	    }

        $retorno = (array)$this->xml->Servicos->cServico;

        if ($retorno['MsgErro'] != '') {
            Cart::setCartFreightMessage($retorno['MsgErro']);
        } else {
            Cart::clearCartFreightMessage();
        }

        $this->setvlfreight(Cart::formatValueToDecimal($retorno['Valor']));
        $this->setnrdays((int)$retorno['PrazoEntrega']);
        $this->setdeszipcode($nrzipcode);

        $this->save();

        return $retorno;

    }

    public static function formatValueToDecimal($value):float
    {

        $value = str_replace('.', '', $value);
        $value = str_replace(',', '.', $value);

        return $value;

    }

    public static function setCartFreightMessage($message)
    {

        $_SESSION[Cart::SESSION_MSG] = $message;

    }

    public static function getCartFreightMessage()
    {

        return (isset($_SESSION[Cart::SESSION_MSG])) ? $_SESSION[Cart::SESSION_MSG] : '';

    }

    public static function clearCartFreightMessage()
    {

        $_SESSION[Cart::SESSION_MSG] = NULL;

    }

}

 ?>