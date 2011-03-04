<?php
/**
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace aura\web;

/**
 * 
 * Generate and validate CSRF tokens.
 * 
 */
class Csrf
{
    /** @var string Project unique key. */
    protected $secret_key;
    
    /** @var string Something unique to the user. NOT a password! */
    protected $user_id;

    /** @var string Hash algorithm. */
    protected $hash_algo;
    
    /** @var integer Time in seconds before a token expires. */
    protected $timeout;



    /**
     * 
     * NOTE: Each project should have a unique and random $secret_key.
     * 
     * The $user_id must be something unique to the user and does not change
     * between requests. This allows the token to be linked to one user. 
     * The $user_id could be an email address or the primary key from the users 
     * table, anything unique to the user, except for passwords will do.
     * 
     * @param string  $secret_key Project unique key.
     * 
     * @param mixed   $user_id    Unique id for a user i.e. email address.
     * 
     * @param integer $timeout    In seconds. Default is 30 minutes.
     * 
     * @param string  $hash_algo  Hashing algorithm for hash_hmac().
     * 
     * @todo cli script to generate random keys
     * 
     */
    public function __construct($secret_key, $user_id, $timeout = 1800, $hash_algo = 'sha1')
    {
        $this->secret_key = $secret_key;
        $this->user_id    = $user_id;
        $this->timeout    = $timeout;
        $this->hash_algo  = $hash_algo;
    }
    
    /**
     * 
     * Magic get to provide access to the hash_algo and timeout variables.
     * 
     * @throws \UnexpectedValueException
     * 
     * @param string $key The property to retrieve: hash_algo & timeout.
     * 
     * @return mixed
     * 
     */
    public function __get($key)
    {
        if ($key == 'hash_algo' || $key == 'timeout') {
            return $this->$key;
        }
        
        throw new \UnexpectedValueException($key);
    }
    
    /**
     * 
     * Magic set to provide access to the secret_key, hash_algo and timeout 
     * variables.
     * 
     * @throws \UnexpectedValueException
     * 
     * @param string $key The property to set: secret_key, hash_algo & timeout.
     * 
     * @return void
     * 
     */
    public function __set($key, $value)
    {
        if ($key == 'secret_key' || $key == 'hash_algo' || $key == 'timeout') {
            $this->$key = $value;
            return;
        }
        
        throw new \UnexpectedValueException($key);
    }
    
    /**
     * 
     * Generate a CSRF token.
     * 
     * @return string 
     * 
     */
    public function generateToken()
    {
        if (func_num_args()) {
            // Used by isValidToken()
            $rawtoken = func_get_arg(0);
        } else {
            $rawtoken = time() . '|' . uniqid(mt_rand(), true);
        } 
        
        // we want the user_id to remain secret just incase it's an email 
        // address or worse
        $hashtoken = hash_hmac($this->hash_algo, $rawtoken . $this->user_id, 
                                $this->secret_key);
        
        return $hashtoken . '|' . $rawtoken;
    }
    
    /**
     * 
     * Test if a token is valid and has not timed out.
     * 
     * If the incoming token is not propertly formated the exception 
     * aura\csrf\Exception_InvalidTokenFormat will be thrown.
     * 
     * @throws aura\csrf\Exception_InvalidTokenFormat
     * 
     * @param string $incoming_token
     * 
     * @return boolean 
     * 
     */
    public function isValidToken($incoming_token)
    {
        $token = explode('|', $incoming_token);
        
        if (3 != count($token)) {
            throw new Exception_InvalidTokenFormat();
        }
        
        list($hashtoken, $time, $uid) = $token;
        
        // generate a token from the incoming values $time|$uid
        if ($incoming_token != $this->generateToken($time . '|' . $uid)) {
            return false;
        }
        
        // Token has expired.
        if ($time + $this->timeout <= time()) {
            return false;
        }
        
        return true;
    }
}