<?php 

namespace Hcode\Model;

use \Hcode\DB\Sql;
use \Hcode\Model;
use \Hcode\Model\Product;

class Category extends Model {

	public static function listAll()
	{

		$sql = new Sql();

		return $sql->select("SELECT * FROM tb_categories ORDER BY descategory");

	}

	public function getProductsPage($page = 1, $itemsPerPage = 10)
	{

		$start = ($page - 1) * $itemsPerPage;
		$length = $itemsPerPage;

		$sql = new Sql();

		$rows = $sql->select("
			SELECT SQL_CALC_FOUND_ROWS *
			FROM tb_products a 
			LEFT JOIN tb_categoriesproducts b USING(idproduct)
			LEFT JOIN tb_categories c USING(idcategory)
			WHERE c.idcategory = :idcategory
			ORDER BY descategory
			LIMIT $start, $length
		", [
			':idcategory'=>$this->getidcategory()
		]);

		$resultTotal = $sql->select("
			SELECT FOUND_ROWS() AS nrtotal
		");

		return [
			'data'=>Product::formatProducts($rows),
			'total'=>(int)$resultTotal[0]['nrtotal'],
			'pages'=>ceil($resultTotal[0]['nrtotal'] / $itemsPerPage)
		];

	}

	public function getProducts($related = true)
	{

		$sql = new Sql();
		
		if ($related === true) {
			return $sql->select("
				SELECT * 
				FROM tb_products a 
				LEFT JOIN tb_categoriesproducts b USING(idproduct)
				LEFT JOIN tb_categories c USING(idcategory)
				WHERE c.idcategory = :idcategory
				ORDER BY descategory
			", [
				':idcategory'=>$this->getidcategory()
			]);
		} else {
			return $sql->select("
				SELECT * 
				FROM tb_products
				WHERE idproduct NOT IN(
					SELECT a.idproduct
					FROM tb_products a 
					LEFT JOIN tb_categoriesproducts b USING(idproduct)
					LEFT JOIN tb_categories c USING(idcategory)
					WHERE c.idcategory = :idcategory
					ORDER BY descategory
				)
			", [
				':idcategory'=>$this->getidcategory()
			]);
		}		

	}

	public function addProduct(Product $product)
	{

		$sql = new Sql();

		$sql->query("INSERT INTO tb_categoriesproducts (idcategory, idproduct) VALUES(:idcategory, :idproduct)", [
			':idcategory'=>$this->getidcategory(),
			':idproduct'=>$product->getidproduct()
		]);

	}

	public function removeProduct(Product $product)
	{

		$sql = new Sql();

		$sql->query("DELETE FROM tb_categoriesproducts WHERE idcategory = :idcategory AND idproduct = :idproduct", [
			':idcategory'=>$this->getidcategory(),
			':idproduct'=>$product->getidproduct()
		]);

	}

    public function save()
	{

		$sql = new Sql();

		$results = $sql->select("CALL sp_categories_save(:idcategory, :descategory)", array(
			":idcategory"=>$this->getidcategory(),
			":descategory"=>$this->getdescategory()
		));

		$this->setData($results[0]);

		Category::updateHTML();

	}

    public function get($idcategory)
	{

		$sql = new Sql();

		$results = $sql->select("SELECT * FROM tb_categories WHERE idcategory = :idcategory", array(
			":idcategory"=>$idcategory
		));

		$this->setData($results[0]);

	}

    public function delete()
	{

		$sql = new Sql();

		$sql->query("DELETE FROM tb_categories WHERE idcategory = :idcategory", array(
			":idcategory"=>$this->getidcategory()
		));

		Category::updateHTML();

	}

	public static function updateHTML()
	{

		$categories = Category::listAll();

		$html = '
			<div class="footer-menu">
				<h2 class="footer-wid-title">Categorias</h2>
				<ul>
					
					
					
				
		';

		foreach ($categories as $cat) {
			$html .= '<li><a href="/categories/'.$cat['idcategory'].'">'.$cat['descategory'].'</a></li>';
		}

		$html .= '
				</ul>
			</div>
		';

		$footer = file_put_contents(
			$_SERVER['DOCUMENT_ROOT'].
			DIRECTORY_SEPARATOR.
			"views".
			DIRECTORY_SEPARATOR.
			"categories-menu.html", $html); 

	}

}

 ?>