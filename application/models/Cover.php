<?php
/**
 * @name coverage
 * @desc 覆盖率
 * @author huangweining
 */


Class CoverModel extends Dao_BaseModel{
	
	const TABLE ='coverage';
	 public static $arrFields = array (
                        'id',
                        'taskid',
                        'report',
                        'create_time',
        );
	public  function addCoverageData($data){
		$arrFields = array(
				'taskid' => $data['taskid'],
				'report' => json_encode($data['report']),
				'create_time' => time()
								
		);
		if (empty ( $this->_db )) {
			$this->_db = self::getDB ( self::DATABASE );
		}
		return $this->_db->insert ( self::TABLE, $arrFields, null, null );
	}
	
	public function getCoverageList($pn=0) {
		if (empty ( $this->_db )) {
                        $this->_db = self::getDB ( self::DATABASE );
                }
		$arrAppends[] = "limit $pn, 10";
		$ret = $this->_db->select( self::TABLE, self::$arrFields, null, null ,$arrAppends);
		return count ( $ret ) > 0 ? $ret: array ();
	}	

	public function getCoverageData($taskId) {
		if (empty ( $this->_db )) {
                        $this->_db = self::getDB ( self::DATABASE );
                }
                $arrConds = self::getConds ( [
                                'id' => $taskId
                ] );
                $ret = $this->_db->select ( self::TABLE, self::$arrFields, $arrConds, null, null );
                return count ( $ret ) > 0 ? $ret[0] : array ();
	}	
}
