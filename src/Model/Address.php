<?php 

namespace Hcode\Model;

use \Hcode\DB\Sql;
use \Hcode\Model;

class Address extends Model {

    public static function getCEP($cep)
    {

        $cep = str_replace('-', '', $cep);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://viacep.com.br/ws/$cep/json/");

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $data = json_decode(curl_exec($ch), true);

        curl_close($ch);

        return $data;

    }

    public function loadFromCEP($cep)
    {

        $data = Address::getCEP($cep);

        if (isset($data['logradouro'])) {

            $this->setdesaddress($data['logradouro']);
            $this->setdescomplement($data['complemento']);
            $this->setdesdistrict($data['bairro']);
            $this->setdescity($data['localidade']);
            $this->setdesstate($data['uf']);
            $this->setdescountry('Brasil');
            $this->setnrzipcode($cep);

        }

    }

    public function save()
	{

		$sql = new Sql();

		$results = $sql->select("CALL sp_addresses_save(:idaddress, :idperson, :desaddress, :descomplement, :descity, :desstate, :descountry, :deszipcode, :desdistrict)", array(
			":idaddress"=>$this->getidaddress(),
			":idperson"=>$this->getidperson(),
			":desaddress"=>$this->getdesaddress(),
			":descomplement"=>$this->getdescomplement(),
			":descity"=>$this->getdescity(),
			":desstate"=>$this->getdesstate(),
			":descountry"=>$this->getdescountry(),
			":deszipcode"=>$this->getdeszipcode(),
			":desdistrict"=>$this->getdesdistrict()
		));

		$this->setData($results[0]);

	} 

}

 ?>