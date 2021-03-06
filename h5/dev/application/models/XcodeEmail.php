<?php

class XcodeEmailModel extends F_Model_Pdo
{
	protected $_table = 'xcode_email';
	protected $_primary = 'email';
	
	public function getTableLabel()
	{
		return '验证码邮箱发送日志';
	}
	
	/**
	 * 发送邮箱验证码
	 * 
	 * @param string $email
	 * @param string $subject
	 * @param string $message
	 * @return mixed
	 */
	public function send($email, $subject, $message)
	{
	    $time = time();
	    $conds = "email='{$email}'";
	    $xe_log = $this->fetch($conds);
	    if( $xe_log ) {
	        if( ($xe_log['send_time'] + 60) > $time ) {
	            return '邮件已发出，请稍后！';
	        }
	        if( $xe_log['day_times'] >= 10 ) {
	            return '每邮箱每日至多可发送10封邮件！';
	        }
	    }
	    
	    $xcode = mt_rand(100000, 999999);
	    if( $xe_log ) {
	        $pdo = $this->getPdo();
	        $rs = $pdo->exec("UPDATE xcode_email SET xcode='{$xcode}',send_time='{$time}',day_times=day_times+1 WHERE {$conds}");
	    } else {
	        $rs = $this->insert(array(
	            'email' => $email,
	            'xcode' => $xcode,
	            'send_time' => $time,
	            'day_times' => 1,
	        ), false);
	    }
	    if( ! $rs ) {
	        return '邮箱验证码发送失败，请稍后重试！';
	    }
	    
	    $message = str_replace('{xcode}', $xcode, $message);
	    
	    //发送验证码
	    $mailer = new F_Helper_Mail();
	    $mailer->useYafConf();
	    $rs = $mailer->send($email, $subject, $message);
	    
	    if( $rs == false ) {
	        return '邮件发送失败，请稍后再试';
	    }
	    
	    return $xcode;
	}
	
	/**
	 * 判断验证码是否有效
	 * 
	 * @param string $email
	 * @param int $xcode
	 * @return string $err
	 */
	public function check($email, $xcode)
	{
	    if( strlen($xcode) != 6 ) {
	        return '邮箱验证码格式错误！';
	    }
	    $time = time();
	    $mb_log = $this->fetch("email='{$email}'");
	    if( empty($mb_log) ) {
	        return '邮箱验证码未发送成功，请重新发送！';
	    }
	    if( ($mb_log['send_time'] + 1800) < $time ) {
	        return '邮箱验证码已过期，请重新发送！';
	    }
	    if( strcmp($mb_log['xcode'], $xcode) != 0 ) {
	        return '邮箱验证码错误，请核对！';
	    }
	    return '';
	}
	
	//重置验证日志
	public function clear()
	{
	    $time = strtotime(date('Y-m-d')) - 600;
	    $this->delete("send_time<{$time}");
	}
}
