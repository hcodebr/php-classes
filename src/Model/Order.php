<?php 

namespace Hcode\Model;

use \Hcode\DB\Sql;
use \Hcode\Model;
use \Hcode\Model\Cart;

class Order extends Model {

	const SESSION_MSG_SUCCESS = "Order_Msg_Success";
	const SESSION_MSG_ERROR = "Order_Msg_Error";

	public static function listAll()
	{

		$sql = new Sql();

		return $sql->select("
			SELECT * 
			FROM tb_orders a
			INNER JOIN tb_ordersstatus b USING(idstatus)
			INNER JOIN tb_carts c USING(idcart)
			INNER JOIN tb_users d ON d.iduser = a.iduser
			INNER JOIN tb_addresses e USING(idaddress)
			INNER JOIN tb_persons f ON f.idperson = d.idperson
			ORDER BY a.dtregister DESC
		");

	}

	public function getSearchPage($search, $page = 1, $itemsPerPage = 10)
	{

		$start = ($page - 1) * $itemsPerPage;
		$length = $itemsPerPage;

		$sql = new Sql();

		$rows = $sql->select("
			SELECT * 
			FROM tb_orders a
			INNER JOIN tb_ordersstatus b USING(idstatus)
			INNER JOIN tb_carts c USING(idcart)
			INNER JOIN tb_users d ON d.iduser = a.iduser
			INNER JOIN tb_addresses e USING(idaddress)
			INNER JOIN tb_persons f ON f.idperson = d.idperson
			WHERE a.idorder = :id OR f.desperson LIKE :search OR f.desemail LIKE :search
			ORDER BY a.dtregister DESC
			LIMIT $start, $length
		", [
			':id'=>(int)$search,
			':search'=>'%'.utf8_decode($search).'%'
		]);

		$resultTotal = $sql->select("
			SELECT FOUND_ROWS() AS nrtotal
		");

		return [
			'data'=>$rows,
			'total'=>(int)$resultTotal[0]['nrtotal'],
			'pages'=>ceil($resultTotal[0]['nrtotal'] / $itemsPerPage)
		];

	}

	public function getPage($page = 1, $itemsPerPage = 10)
	{

		$start = ($page - 1) * $itemsPerPage;
		$length = $itemsPerPage;

		$sql = new Sql();

		$rows = $sql->select("
			SELECT * 
			FROM tb_orders a
			INNER JOIN tb_ordersstatus b USING(idstatus)
			INNER JOIN tb_carts c USING(idcart)
			INNER JOIN tb_users d ON d.iduser = a.iduser
			INNER JOIN tb_addresses e USING(idaddress)
			INNER JOIN tb_persons f ON f.idperson = d.idperson
			ORDER BY a.dtregister DESC
			LIMIT $start, $length
		");

		$resultTotal = $sql->select("
			SELECT FOUND_ROWS() AS nrtotal
		");

		return [
			'data'=>$rows,
			'total'=>(int)$resultTotal[0]['nrtotal'],
			'pages'=>ceil($resultTotal[0]['nrtotal'] / $itemsPerPage)
		];

	}

    public function save()
    {

        $sql = new Sql();

		$results = $sql->select("CALL sp_orders_save(:idorder, :idcart, :iduser, :idstatus, :idaddress, :vltotal)", array(
			":idorder"=>$this->getidorder(),
			":idcart"=>$this->getidcart(),
			":iduser"=>$this->getiduser(),
			":idstatus"=>$this->getidstatus(),
			":idaddress"=>$this->getidaddress(),
			":vltotal"=>$this->getvltotal()
		));

		$this->setData($results[0]);

    }

	public function get($idorder)
	{

		$sql = new Sql();

		$results = $sql->select("
			SELECT * 
			FROM tb_orders a
			INNER JOIN tb_ordersstatus b USING(idstatus)
			INNER JOIN tb_carts c USING(idcart)
			INNER JOIN tb_users d ON d.iduser = a.iduser
			INNER JOIN tb_addresses e USING(idaddress)
			INNER JOIN tb_persons f ON f.idperson = d.idperson
			WHERE idorder = :idorder
		", array(
			":idorder"=>$idorder
		));

		$this->setData($results[0]);

	}

	public function getCart()
	{

		$cart = new Cart();

		$cart->setData($this->getValues());

		return $cart;

	}

	public function delete()
	{

		$sql = new Sql();

		$sql->query("DELETE FROM tb_orders WHERE idorder = :idorder", array(
			":idorder"=>$this->getidorder()
		));

	}

	public static function setSuccess($message)
    {
        $_SESSION[Order::SESSION_MSG_SUCCESS] = $message;
	}

	public static function setError($message)
    {
        $_SESSION[Order::SESSION_MSG_ERROR] = $message;
	}
	
    public static function getSuccess()
    {
		$msg = (isset($_SESSION[Order::SESSION_MSG_SUCCESS])) ? $_SESSION[Order::SESSION_MSG_SUCCESS] : '';
		
		Order::clearSuccess();

		return $msg;

	}

	public static function getError()
    {
		$msg = (isset($_SESSION[Order::SESSION_MSG_ERROR])) ? $_SESSION[Order::SESSION_MSG_ERROR] : '';
		
		Order::clearError();

		return $msg;

	}
	
    public static function clearSuccess()
    {
        $_SESSION[Order::SESSION_MSG_SUCCESS] = NULL;
	}
	
	public static function clearError()
    {
        $_SESSION[Order::SESSION_MSG_ERROR] = NULL;
    }

}

?>