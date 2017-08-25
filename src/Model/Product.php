<?php 

namespace Hcode\Model;

use \Hcode\DB\Sql;
use \Hcode\Model;

class Product extends Model {

	public static function listAll()
	{

		$sql = new Sql();

		return $sql->select("SELECT * FROM tb_products ORDER BY desproduct");

	}

    public function save()
	{

		$sql = new Sql();

		$results = $sql->select("CALL sp_produtcs_save(:idproduct, :desproduct, :vlprice, :vlwidth, :vlheight, :vllength, :vlweight)", array(
			":idproduct"=>$this->getidproduct(),
			":desproduct"=>$this->getdesproduct(),
			":vlprice"=>$this->getvlprice(),
			":vlwidth"=>$this->getvlwidth(),
			":vlheight"=>$this->getvlheight(),
			":vllength"=>$this->getvllength(),
			":vlweight"=>$this->getvlweight()
		));

		$this->setData($results[0]);

	}

    public function get($idproduct)
	{

		$sql = new Sql();

		$results = $sql->select("SELECT * FROM tb_products WHERE idproduct = :idproduct", array(
			":idproduct"=>$idproduct
		));

		$this->setData($results[0]);

	}

    public function delete()
	{

		$sql = new Sql();

		$sql->query("DELETE FROM tb_products WHERE idproduct = :idproduct", array(
			":idproduct"=>$this->getidproduct()
		));

	}

	public function checkPhoto()
	{

		$path = 
			$_SERVER['DOCUMENT_ROOT'] . 
			DIRECTORY_SEPARATOR . 
			"res" . 
			DIRECTORY_SEPARATOR . 
			"site" . 
			DIRECTORY_SEPARATOR . 
			"img" . 
			DIRECTORY_SEPARATOR . 
			"products" . 
			DIRECTORY_SEPARATOR;

		if (file_exists($path . $this->getidproduct() . '.jpg')) {

			$this->setdesphoto("/res/site/img/products/" . $this->getidproduct() . ".jpg");

		} else {

			$this->setdesphoto("http://placehold.it/250x250");

		}

	}

	public function getValues()
	{
		
		$this->checkPhoto();

		$values = parent::getValues();

		return $values;

	}

	public static function formatProducts($list)
	{

		foreach ($list as &$data) {

			$product = new Product();

			$product->setData($data);

			$data = $product->getValues();

		}

		return $list;

	}

	public function getCategories()
	{

		$sql = new Sql();

		return $sql->select("SELECT * FROM tb_categories a INNER JOIN tb_categoriesproducts b USING(idcategory) WHERE idproduct = :idproduct", [
			':idproduct'=>$this->getidproduct()
		]);

	}

}

 ?>