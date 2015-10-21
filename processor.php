<?php
/**
 * 学生查询管理模块处理程序
 *
 * @author mais
 * @url http://bbs.we7.cc/
 */
defined('IN_IA') or exit('Access Denied');

class Mais_studentsModuleProcessor extends WeModuleProcessor
{
	public function respond()
	{
		$content = $this->message['content'];
		if($content)
		{
			if($this->CheckLogin())
			{
				$returnData = $this->Routes($content);
				return $this->respText($returnData);
			}
			else
			{
				return $this->respText('您没有权利查询！');
			}
		}
		else
		{
			return $this->respText('Sorry');
		}


	}

	private function Routes($content)
	{
		if($this->inContext)
		{
			if(preg_match('/退出/',$content))
			{
				$this->endContext();
				return '已退出，谢谢！';
			}
			return($this->Search($content));
		}
		else
		{
			$this->beginContext();
			return '欢迎使用微风学生管理系统,您可以输入学号+[-s]查询学生最近情况！';
		}
	}


	private function Search($content)
	{
		preg_match_all('/(\d.*)-s/',$content,$temp);
		$IDNumber = $temp[1][0];
		if($IDNumber)
		{
			$StudentInfoSql = 'SELECT * FROM '.tablename('lar_stu').' WHERE SchoolId = :SchoolId ';
			$StudentInfoData = pdo_fetch($StudentInfoSql,[':SchoolId' => $IDNumber]);
			$TeacherInfoSql = 'SELECT * FROM '.tablename('lar_log').' WHERE schoolid = :schoolid ORDER BY time DESC LIMIT 0,5 ';
			$TeacherInfoData = pdo_fetchall($TeacherInfoSql,[':schoolid' => $IDNumber]);
			$TeacherInfoString = '';
			foreach($TeacherInfoData as $key => $value)
			{
				$TeacherInfoString .= ($key+1).'、'.PHP_EOL.'课号：'.$value['coursenumber'].PHP_EOL.'行为描述：'.$value['des'].
				PHP_EOL.'记录人：'.$value['teacher'].PHP_EOL.'记录时间：'.$value['time'].PHP_EOL;
			}
			$explode = '----------------';
			$StudentInfoString = $StudentInfoData['name'].PHP_EOL.$StudentInfoData['SchoolId'].PHP_EOL.$StudentInfoData['Detail'].PHP_EOL .$explode
			.PHP_EOL.$TeacherInfoString;
			return $StudentInfoString;
		}
		else
		{
			//return 'Error';
		}
	}


	private function CheckLogin()
	{
		global $_W;
		$CheckLoginSql = 'SELECT * FROM '.tablename('lar_admin').' WHERE profit = :openid ';
		$CheckLoginData = pdo_fetch($CheckLoginSql,[':openid' => $_W['openid']]);
		if($CheckLoginData)
		{
			return 1;
		}
		else
		{
			return 0;
		}
	}
}