<?php

/**
 * @name Product
 * @desc Product数据获取类, 可以访问数据库，文件，其它系统等
 * @author wangjian
 */
class ProductModel extends Dao_BaseModel {
	const PIC_URL = '/static/picture/';
	const TABLE = 'product';
	const HOT = 1;
	const PAGE = 11; // 默认翻页是10
	public static $arrFields = array (
			'id',
			'name',
			'type',
			'standard',
			'price',
			'sold',
			'color',
			'area',
			'model',
			'material',
			'picture',
			'series',
			'hot',
			'createTime',
			'status' 
	);
	public function getProductInfoById($pid) {
		if (empty ( $this->_db )) {
			$this->_db = self::getDB ( self::DATABASE );
		}
		$arrConds = self::getConds ( [ 
				'id' => $pid 
		] );
		$ret = $this->_db->select ( self::TABLE, self::$arrFields, $arrConds, null, null );
		if(count($ret) > 0) {
			$ret[0]['picture'] = $this->_formatPicture($ret[0]['picture']);
			return $ret[0];
		}
		return array ();
	}
	public function getHotProductList() {
		if (empty ( $this->_db )) {
			$this->_db = self::getDB ( self::DATABASE );
		}
		$arrConds = self::getConds ( [ 
				'hot' => self::HOT 
		] );
		$arrAppends = array (
				'order by id desc',
				'limit ' . self::PAGE 
		);
		$ret = $this->_db->select ( self::TABLE, self::$arrFields, $arrConds, null, $arrAppends );
		if(count($ret) > 0) {
			foreach($ret as &$item) {
				$item['picture'] = $this->_formatPicture($item['picture']);
			}
			return $ret;
		}
		return array ();
	}
	public function getProductList($filter = array(), $order = '', $pn = 0, $rn = 11) {
		if (empty ( $this->_db )) {
			$this->_db = self::getDB ( self::DATABASE );
		}
		$arrConds = self::getConds($filter);
		if ($order) {
			$arrAppends = array (
					"order by $order desc",
					"limit $pn, $rn" 
			);
		} else {
			$arrAppends = array (
					"limit $pn, $rn" 
			);
		}
		$ret = $this->_db->select ( self::TABLE, self::$arrFields, $arrConds, null, $arrAppends );
		if(count($ret) > 0) {
			foreach($ret as &$item) {
				$item['picture'] = $this->_formatPicture($item['picture']);
			}
			return $ret;
		}
		return array ();
	}
	public function getProductCount() {
		if (empty ( $this->_db )) {
			$this->_db = self::getDB ( self::DATABASE );
		}
		return $this->_db->selectCount(self::TABLE);
	}
	public function searchProduct($words, $pn=10) {
		if (empty ( $this->_db )) {
			$this->_db = self::getDB ( self::DATABASE );
		}
		$sql = "select %s from %s where name like '%%%s%%' limit %d,11";
		$sql = sprintf($sql, implode(',', self::$arrFields), self::TABLE, $words, $pn);
		$ret = $this->_db->query($sql);
		if(count($ret) > 0) {
			foreach($ret as &$item) {
				$item['picture'] = $this->_formatPicture($item['picture']);
			}
			return $ret;
		}
		return array ();
 	}
	public function addProduct($arrInput) {
		$arrFields = array (
				'name' => trim ( $arrInput ['name'] ),
				'type' => intval ( $arrInput ['type'] ),
				'standard' => trim ( $arrInput ['standard'] ),
				'price' => intval ( $arrInput ['price'] ),
				'color' => trim ( $arrInput ['color'] ),
				'area' => trim ( $arrInput ['area'] ),
				'model' => trim ( $arrInput ['model'] ),
				'material' => trim ( $arrInput ['material'] ),
				'picture' => json_encode($arrInput ['picture']),
				'series' => $arrInput ['series'],
				'hot' => self::HOT,
				'createTime' => time (),
				'status' => 0 
		);
		if (empty ( $this->_db )) {
			$this->_db = self::getDB ( self::DATABASE );
		}
		return $this->_db->insert ( self::TABLE, $arrFields, null, null );
	}
	public function setHotProduct($pid, $hot) {
		return $this->updateProduct ( $pid, [ 
				'hot' => $hot 
		] );
	}
	public function setStatusProduct($pid, $status) {
		return $this->updateProduct ( $pid, [ 
				'status' => $status 
		] );
	}
	public function incSoldProduct($pid) {
		$sql = 'update '. self::TABLE . ' set sold = sold + 1 where id = '. $pid;
		if (empty ( $this->_db )) {
			$this->_db = self::getDB ( self::DATABASE );
		}
		$this->_db->query($sql);
		return $this->_db->affectedRows;
	}
	public function updateProduct($pid, $arrFields) {
		$arrConds = self::getConds ( [ 
				'id' => $pid 
		] );
		if (empty ( $this->_db )) {
			$this->_db = self::getDB ( self::DATABASE );
		}
		return $this->_db->update ( self::TABLE, $arrFields, $arrConds );
	}
	private function _formatPicture($picture) {
		$tmp = json_decode($picture, true);
		if(!is_array($tmp)) {
			return array();
		}
		return array(
			'big' => self::PIC_URL . $tmp['big'],
			'small' => self::PIC_URL . $tmp['small']
		);
	}
}
?>
