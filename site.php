<?php
/**
 * 学生查询管理模块微站定义
 *
 * @author mais
 * @url http://bbs.we7.cc/
 */
defined('IN_IA') or exit('Access Denied');
define('SALT','56984AD%^%hjjh');

class Mais_studentsModuleSite extends WeModuleSite {

	/**
	 * 定义登陆方法
	 */
	public function doMobileIndex()
	{
		global $_W ,$_GPC;
		$MobileLoginCheckSql = 'SELECT * FROM '.tablename('lar_admin').' WHERE profit = :openid ';
		$MobileLoginCheckData = pdo_fetch($MobileLoginCheckSql,[':openid' => $_W['openid']]);
		if ($MobileLoginCheckData)
		{
			message($MobileLoginCheckData['username'].'您好！欢迎回来！',
				$this->createMobileUrl('Search'),'success');
		}
		else
		{
			if(checksubmit())
			{
				$MobileIndexLoginSql = 'SELECT password,state FROM '.tablename('lar_admin').' WHERE username = :username ';
				$MobileIndexLoginData = pdo_fetch($MobileIndexLoginSql,array(':username' => $_GPC['username']));
				$MobileIndexLoginInsertData['profit'] = $_W['openid'];
				$MobileIndexLoginInsertRes = pdo_update('lar_admin',$MobileIndexLoginInsertData,['username' => $_GPC['username']]);
				if($MobileIndexLoginData['password'] == md5($_GPC['password'].SALT) || $MobileIndexLoginInsertRes)
				{
					message($_GPC['username'].'您好！欢迎回来！',
						$this->createMobileUrl('Search'),'success');
				}
				else
				{
					message('对不起，请检查用户名和密码！','refresh','error');
				}
			}
			include $this->template('MobileIndex');
		}
	}


	/**
	 * 后台权限管理
	 */
	public function doWebAdmin()
	{
		global $_W , $_GPC;
		if(checksubmit())
		{
			$WebAdminInsertData['username'] = $_GPC['Username'];
			$WebAdminInsertData['password'] = md5($_GPC['Password'].SALT);
			$WebAdminInsertData['userid'] = $_GPC['UserID'];
			$WebAdminInsertRes = pdo_insert('lar_admin',$WebAdminInsertData);
			if($WebAdminInsertRes)
			{
				message('用户,'.$_GPC['Username'].'已成功录入！','refresh','success');
			}
		}
		$AdminSql = 'SELECT * FROM '.tablename('lar_admin');
		$AdminData = pdo_fetchall($AdminSql);
		include $this->template('WebAdmin');
	}

	/**
	 * 查询界面
	 */
	public function doMobileSearch()
	{
		global $_W , $_GPC;
		$TeacherInfo = $this->MobileSearchForTeacher($_W['openid']);
		$teacher = $TeacherInfo['username'];
		$State = $TeacherInfo['state'];
		/**
		 * 课号查询，异常提交
		 */
		if(checksubmit('sub'))
		{
			foreach($_GPC as $key => $value)
			{
				if(preg_match('/as\d/',$key))
				{
					$LogInsertData['schoolid'] = substr($key,2);
					$LogInsertData['des'] = $value;
					$LogInsertData['detail'] = $_GPC['more'.$LogInsertData['schoolid']];
					$LogInsertData['teacher'] = $_GPC['TeacherName'];
					$LogInsertData['time'] = date('Y-m-d H:i:s');
					$LogInsertData['coursenumber'] = $_GPC['CourseNumber'];
					$LogInsertRes = pdo_insert('lar_log',$LogInsertData);
				}
			}
			if($LogInsertRes)
			{
				message('All Right!','refresh','success');
			}
			else
			{
				message('出错啦！');
			}
			exit();
		}
		/**
		 * 查询入口
		 */
		if(checksubmit())
		{
			$KeyWord = '';
			$Key = 'class';
			if(!empty($_GPC['SchoolId']))
			{
				$KeyWord = $_GPC['SchoolId'];
				$Key = 'SchoolId';

			}
			elseif(!empty($_GPC['class']))
			{
				$KeyWord = $_GPC['class'];
				$Key = 'class';
			}
			elseif(!empty($_GPC['coursenumber']))
			{
				$KeyWord = substr($_GPC['coursenumber'],0,7);
				$Key = 'coursenumber';
			}
			if($Key == 'SchoolId' || $Key == 'class')
			{
				$MobileSearchSql = 'SELECT SchoolId,DenyScore,Detail,name FROM '.tablename('lar_stu').' WHERE '.$Key.' = :KeyWord ORDER BY DenyScore DESC ';
				$MobileSearchData = pdo_fetchall($MobileSearchSql,[':KeyWord' => $KeyWord]);
			}
			else
			{
				$MobileSearchSql = 'SELECT ims_lar_stu.SchoolId,DenyScore,Detail,name FROM '.tablename('lar_stu').' INNER JOIN '.tablename('lar_plantable')
									.' ON ims_lar_stu.SchoolId = ims_lar_plantable.SchoolId WHERE ims_lar_plantable.coursenumber = :KeyWord ORDER BY DenyScore DESC ';
				$MobileSearchData = pdo_fetchall($MobileSearchSql,[':KeyWord' => $KeyWord]);
				//$gate 课号查询特殊页面布局开关 1 开启
				$gate = 1;
			}
			if($MobileSearchData)
			{
				$AllData = $MobileSearchData;
				include $this->template('MobileShow');
				exit();
			}
			else
			{
				message($_GPC['class'].'没有找到结果！请核对输入信息！','refresh','error');
			}
		}
		else
		{
			$TeacherCourse = $this->MobileSearchForCourse($teacher);
			include $this->template('MobileSearch');
		}
	}

	/**
	 * @param $teacher
	 * @return array
	 * 指定教师课表数组，去除重复元素、返回
	 */
	private function MobileSearchForCourse($teacher)
	{
		$TeacherArraySql = 'SELECT coursename,coursenumber FROM '.tablename('lar_teacher').' WHERE teacher = :teacher ';
		$TeacherArrayData = pdo_fetchall($TeacherArraySql,[':teacher' => $teacher]);
		$target = $TeacherArrayData[0]['coursenumber'];
		for($i = 1;$i < count($TeacherArrayData);$i ++ )
		{
			if($TeacherArrayData[$i]['coursenumber'] == $target)
			{
				array_splice($TeacherArrayData,$i,1);
			}
			$target = $TeacherArrayData[$i]['coursenumber'];
		}
		return$TeacherArrayData;
	}

	/**
	 * @param $openid
	 * @return bool
	 */
	private function MobileSearchForTeacher($openid)
	{
		$MobileSearchTeacherSql = 'SELECT username,state FROM '.tablename('lar_admin').' WHERE profit = :openid ';
		$MobileSearchTeacherData = pdo_fetch($MobileSearchTeacherSql,[':openid' => $openid]);
		return $MobileSearchTeacherData;
	}


	public function doMobileHistory()
	{
		global $_W , $_GPC;
		$teacher = $_GPC['teacher'];
		$MobileHistorySql = 'SELECT * FROM '.tablename('lar_log').' WHERE teacher = :teacher ';
		$MobileHistoryData = pdo_fetchall($MobileHistorySql,[':teacher' => $teacher]);
		include $this->template('MobileHistory');
	}




}