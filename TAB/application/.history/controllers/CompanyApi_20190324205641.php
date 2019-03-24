<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class CompanyApi extends CI_Controller {

    function __construct() 
    {
		parent::__construct();
		$this->load->helper('url'); 
		$this->load->helper('string');
		$this->load->library('session');
		//$this->load->library('mail');
        $this->load->library('form_validation');
        $this->load->helper(array('form', 'url', 'captcha'));
        $this->load->model('commonModel');
        date_default_timezone_set('Asia/Kolkata');
    }


	/**
     * CodeIgniter
     * @package        NTANA
 	 * @author          Chinnu
     * @since           Version 1.0
 	 * Date:            23-03-2019
	 * Description: 	Check- Email already Exist
     */	
	  
    public function checkEmail()
    {
            $email = $this->input->post('email');
            $email = $columns = 'count(id) as cnt';
            $condtion = ['email' => $email];
            $emailExist = $this->commonModel->selectDataCommon('users',$columns,$condtion);
            $emailcnt = 0;
            foreach($emailExist as $row)
            {
                    $emailcnt = $row['cnt'];
            }
            if($emailcnt > 0)
            {
                    $result = array('status'=> FALSE);
            }
            else
            {
                    $result = array('status'=> TRUE);
            }
            echo json_encode($result);
    }
	
	/**
     * CodeIgniter
     * @package        NTANA
 	 * @author          Chinnu
     * @since           Version 1.0
 	 * Date:            23-03-2019
	 * Description: 	User registration
     */	
	public function register()
	{   
            $this->form_validation->set_rules('organization', 'Organization Name', 'required|strip_tags');
            $this->form_validation->set_rules('name', 'Name', 'required|strip_tags');
            $this->form_validation->set_rules('contact_no', 'Mobile', 'required|strip_tags');
            $this->form_validation->set_rules('email', 'Email', 'required|strip_tags|valid_email|matches[confirm_email]|is_unique[users.email]', array(
                    'required'      => 'You have not provided a valid %s.',
                    'is_unique'     => 'This %s already exists.'
            ));
            $this->form_validation->set_rules('confirm_email', 'Confirm Email', 'required');
			$this->form_validation->set_rules('password', 'Password', 'required|matches[confirm_password]');
			$this->form_validation->set_rules('confirm_password', 'Confirm Password', 'required');

			if ($this->form_validation->run() == FALSE)
			{ 
                    $result = array('status' => FALSE, 'error' => $this->form_validation->error_array());
    		}
			else
			{
                    $data['organization'] = $this->input->post('organization');
                    $data['name'] = $this->input->post('name');
                    $data['phone_no'] = $this->input->post('contact_no');
                    $data['email'] = trim($this->input->post('email'));
                    $data['password'] = $this->input->post('password');
                    $data['role_id'] = USER;
                    $data['status'] = EMAIL_NOT_VERIFIED;
                    $data['created_at'] = date("Y-m-d H:i:s");
                    $data['email_verification_code'] = md5($this->randomString(10));
                            //user data insertion......
                    $details = $this->commonModel->insertData('users', $data); 
                    if($details > 0)
                    {   
                        if($data['email'] != NULL)
                        {
                            
                            $email1 = $data['email'];
                            $this->load->library('mail');
                            $subject = "Natana";
                            #$data['email_subject'] = $subject;
                            $url = site_url('registration/validate-email?mail='.$data['email'].'token='.$data['email_verification_code']);
                            
                            $content = "Dear ".$data['name'].", <br/>Thank you for registering with 'TAB'.<br/>Please click the link to verify your email address : <br/><a href='".$url."' target='blank'> Email verification link </a>";
                            #$content = $this->load->view('user/mail_template_new',$data,TRUE);
                            #$this->mail->send_verification_mail($email1,$subject,$content);
                        }
                        $result = array('status' => TRUE);
                        /// redirect('login');
                    }
                    else
                    {
                        $result = array('status' => FALSE,'msg'=> 'Something went wrong.');
                    }
						
            }
            echo json_encode($result);
	}
	
	
	/**
     * CodeIgniter
     * @package        NTANA
 	 * @author          Chinnu
     * @since           Version 1.0
 	 * Date:            23-03-2019
	 * Description: 	One time password creation
     */	
    public function randomString($length) 
    {
            $key = '';
            $keys = array_merge(range(0, 9), range('a', 'z'));
        
            for ($i = 0; $i < $length; $i++) {
                $key .= $keys[array_rand($keys)];
            }
        
            return $key;
    }
     
	/**
     * CodeIgniter
     * @package        NTANA
 	 * @author          Chinnu
     * @since           Version 1.0
 	 * Date:            23-03-2019
     * Description: 	email verification
     */	
	public function emailVerification()
	{
        $this->form_validation->set_rules('token', 'Token', 'required|strip_tags');
        $this->form_validation->set_rules('email', 'Email', 'required|strip_tags|valid_email');

        if ($this->form_validation->run() == FALSE)
        {
            $result = array('status'=> FALSE, 'msg'=> 'You cant access this URL');
        }
        else
        {
            $email              = $this->input->post('email');
            $verificatioCode   = $this->input->post('token');

            $columns    = 'id,email_verification_code,status';
            $condtion   = ['email' => $email];
            $code = $this->commonModel->selectDataCommon('users',$columns,$condtion);
            if(!empty($code))
            {
                foreach($code as $codes)
                {
                    $useCode = $codes['email_verification_code'];
                    $useStatus = $codes['status'];
                    $userId = $codes['id'];  
                }
                if($useStatus > EMAIL_NOT_VERIFIED)
                {
                    $result = array('status'=> TRUE,'msg'=> 'Email already verified');
                }
                elseif ($verificatioCode === $useCode) 
                {
                    $data['status'] = EMAIL_VERIFIED;
                    $data['email_verification_code'] = '';
                    $condtion   = ['id' => $userId];
                    $stat = $this->commonModel->updateData('users',$data,$condtion);
                    if($stat)
                    {
                        $result = array('status'=> TRUE,'msg'=> 'Success');
                    }
                    else
                    {
                        $result = array('status'=> FALSE,'msg'=> 'Something went wrong.');
                    }

                }
                else
                {
                    $result = array('status'=> FALSE, 'msg'=> 'No such Email ID');
                }
            }
            else
            {
                $result = array('status'=> FALSE, 'msg'=> 'No such Email ID');
            }
        }
        
        echo json_encode($result);
    }
    
    /**
     * CodeIgniter
     * @package        NTANA
 	 * @author          Chinnu
     * @since           Version 1.0
 	 * Date:            23-03-2019 
     * Description:     forget password
     */
    public function passwordResetMail()
    {
            $this->form_validation->set_rules('email', 'Email', 'required|strip_tags|valid_email');
            if ($this->form_validation->run() == FALSE)
            {
                    $result = array('status'=> FALSE, 'msg'=> 'You cant access this URL');
            }
            else
            {
                    $email  = $this->input->post('email');
                    $columns    = 'id,status';
                    $condtion   = ['email' => $email];
                    $emailExist = $this->commonModel->selectDataCommon('users',$columns,$condtion);
                    if(!empty($emailExist))
                    {
                            foreach($emailExist as $row)
                            {
                                $useStatus = $row['status'];
                                $userId = $row['id'];  
                            }
                            if($useStatus == EMAIL_NOT_VERIFIED)
                            {
                                $result = array('status'=> FALSE,'msg'=> 'Email verification process pending');
                            }
                            elseif($useStatus == EMAIL_VERIFIED)
                            {
                                $result = array('status'=> FALSE,'msg'=> 'Account Under administrator approval session');
                            }
                            elseif($useStatus == BLOCKED || $useStatus == DELETED)
                            {
                                $result = array('status'=> FALSE,'msg'=> 'Your account is temerorly suspended please contact administrator');
                            }
                            else 
                            {
                                    $data['email_verification_code'] = md5($this->randomString(10));
                                    $condtion   = ['id' => $userId];
                                    $stat = $this->commonModel->updateData('users',$data,$condtion);
                                    if($stat)
                                    {
                                        $this->load->library('mail');
                                        $subject = "Natana";
                                        #$data['email_subject'] = $subject;
                                        $url = site_url('registration/reset-password?mail='.$email.'token='.$data['email_verification_code']);
                                        
                                        $content = "Dear ".$data['name'].", <br/>Please click the link to reset your password: <br/><a href='".$url."' target='blank'> Reset Password Link </a>";
                                        #$content = $this->load->view('user/mail_template_new',$data,TRUE);
                                        #$this->mail->send_verification_mail($email,$subject,$content);
                                        $result = array('status'=> TRUE,'msg'=> 'Success');
                                    }
                                    else
                                    {
                                        $result = array('status'=> FALSE,'msg'=> 'Something went wrong.');
                                    }
                            }

                    }
                    else
                    {
                        $result = array('status'=> FALSE, 'msg'=> 'No such Email ID');
                    }
            }
    }

	/**
     * CodeIgniter
     * @package        NTANA
 	 * @author          Chinnu
     * @since           Version 1.0
 	 * Date:            23-03-2019 
     * Description:     change password webservice
     */
    public function passwordReset()
    { 
        $this->form_validation->set_rules('token', 'Token', 'required|strip_tags');
        $this->form_validation->set_rules('email', 'Email', 'required|strip_tags|valid_email');
        $this->form_validation->set_rules('confirm_email', 'Confirm Email', 'required');
		$this->form_validation->set_rules('password', 'Password', 'required|matches[confirm_password]');

        if ($this->form_validation->run() == FALSE)
        {
            $result = array('status'=> FALSE, 'msg'=> 'You cant access this URL');
        }
        else
        {
            $email              = $this->input->post('email');
            $verificatioCode   = $this->input->post('token');

            $columns    = 'id,email_verification_code,status';
            $condtion   = ['email' => $email];
            $code = $this->commonModel->selectDataCommon('users',$columns,$condtion);
            if(!empty($code))
            {
                foreach($code as $codes)
                {
                    $useCode = $codes['email_verification_code'];
                    $useStatus = $codes['status'];
                    $userId = $codes['id'];  
                }
                if($useStatus > EMAIL_NOT_VERIFIED)
                {
                    $result = array('status'=> TRUE,'msg'=> 'Email already verified');
                }
                elseif ($verificatioCode === $useCode) 
                {
                    $data['status'] = EMAIL_VERIFIED;
                    $data['email_verification_code'] = '';
                    $condtion   = ['id' => $userId];
                    $stat = $this->commonModel->updateData('users',$data,$condtion);
                    if($stat)
                    {
                        $result = array('status'=> TRUE,'msg'=> 'Success');
                    }
                    else
                    {
                        $result = array('status'=> FALSE,'msg'=> 'Something went wrong.');
                    }

                }
                else
                {
                    $result = array('status'=> FALSE, 'msg'=> 'No such Email ID');
                }
            }
            else
            {
                $result = array('status'=> FALSE, 'msg'=> 'No such Email ID');
            }
        }
        
        echo json_encode($result);











		
        $device = $this->device_validation->check_user_device();
        if($device == '1')
        {
            langs();
            $data['old_password'] = $this->input->post('current_password');
            $data['password'] = $this->input->post('confirm_password');
            $data['user_id'] = $this->input->post('user_id');
            $data['imei_number'] = $this->input->post('imei_number');
            $data['check_value'] = $this->input->post('check_value');
            $user_exist = $this->device_validation->check_user_exist($data['user_id'],$data['imei_number'],$data['check_value']);
            if($user_exist)
            {
                if (($data['user_id'] != '') && ($data['password'] != '') && ($data['old_password'] != '')) 
                {
                    $data['pwd'] = $this->User_model->get_user_pwd($data['user_id']);
                    $data['status'] = $this->User_model->get_user_status($data['user_id']);
                    if($data['pwd'] == ($data['old_password']))
                    {
                        if ($data['status'] == '1') 
                        {   
                                $d['change_password'] = $this->User_model->update_user_password($data);
                                if($d['change_password'])
                                {   
                                    $results = array("status" => '0');
                                    echo json_encode($results);
                                }
                                else
                                {
                                    $results = array("status" => '3');
                                    echo json_encode($results);
                                }
                            
                        }
                        else
                        {
                            $results = array("status" => '2');
                            echo json_encode($results);
                        }
                    }
                    else
                    {
                        $results = array("status" => '1');
                        echo json_encode($results);
                    }
                }
                else
                {
                    $results = array("status" => '4');
                    echo json_encode($results);
                }
            }
            else
            {
                $results = array("status" => '5');
                echo json_encode($results);
            }
            
        }
        else
        {
            $results = array("status" => '6');
            echo json_encode($results);
        }
        

	}
	
	 
}

/* End of file Registration */
/* Location: ./application/controllers/Registration.php */