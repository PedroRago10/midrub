<?php
/**
 * Tickets Model
 *
 * PHP Version 5.6
 *
 * Tickets_model file contains the Tickets Model
 *
 * @category Social
 * @package  Midrub
 * @author   Scrisoft <asksyn@gmail.com>
 * @license  https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License
 * @link     https://www.midrub.com/
 */

// Constants
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Tickets_model class - operates the tickets table.
 *
 * @category Social
 * @package  Midrub
 * @author   Scrisoft <asksyn@gmail.com>
 * @license  https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License
 * @link     https://www.midrub.com/
 */
class Tickets_model extends CI_MODEL {
    
    /**
     * Class variables
     */
    private $table = 'tickets';

    /**
     * Initialise the model
     */
    public function __construct() {
        
        // Call the Model constructor
        parent::__construct();
        
        // Set the tables value
        $this->tables = $this->config->item('tables', $this->table);
        
    }
    
    /**
     * The public method save_ticket saves a new ticket in the database
     *
     * @param integer $user_id contains the user_id
     * @param string $subject contains the ticket's subject
     * @param string $body contains the ticket's body
     * 
     * @return integer with last inserted id or false
     */
    public function save_ticket( $user_id, $subject, $body ) {
        
        // Set data
        $data = array(
            'user_id' => $user_id,
            'subject' => $subject,
            'body' => $body,
            'status' => 1,
            'created' => time()
        );
        
        // Save ticket
        $this->db->insert($this->table, $data);
        
        // Verify if ticket was saved
        if ( $this->db->affected_rows() ) {
            
            return $this->db->insert_id();
            
        } else {
            
            return false;
            
        }
        
    }
    
    /**
     * The public method save_reply saves a ticket's reply in the database
     *
     * @param integer $user_id contains the user_id
     * @param integer $ticket_id contains the ticket's subject
     * @param string $body contains the ticket's body
     * 
     * @return boolean true or false
     */
    public function save_reply( $user_id, $ticket_id, $body ) {
        
        // Load Notifications model
        $this->load->model('Notifications', 'notifications');
        
        // Load User model
        $this->load->model('User', 'user');
        
        $data = array(
            'ticket_id' => $ticket_id,
            'body' => $body,
            'created' => time()
        );
        
        if ( $user_id ) {
            
            $data['user_id'] = $user_id;
            
        } else {
            
            $data['user_id'] = $this->user_id;
            
        }
        
        $this->db->insert('tickets_meta', $data);
        
        if ( $this->db->affected_rows() ) {
            
            $this->db->select('*');
            $this->db->from($this->table);
            $this->db->where(['ticket_id' => $ticket_id]);
            $query = $this->db->get();
            
            if ( $query->num_rows() > 0 ) {
                
                $result = $query->result();
                
                if ( $this->user->get_user_option($result[0]->user_id, 'notification_tickets') ) {
                    
                    if ( $result[0]->user_id != $user_id ) {
                        
                        $this->notifications->send_notification($result[0]->user_id, 'ticket-notification-reply');
                        
                    }
                    
                }
                
            }
            
            return true;
            
        } else {
            
            return false;
            
        }
        
    }
    
    /**
     * The public method get_all_tickets gets all user tickets
     *
     * @param integer $type contains the tickets type
     * @param integer $user_id contains the user_id
     * @param integer $start contains the current page
     * @param integer $limit contains the number of tickets
     * 
     * @return array with all tickets or false
     */
    public function get_all_tickets( $type, $user_id=0, $start=0, $limit=0 ) {
        
        $where = array();
        
        if ( $user_id ) {
            
            $where['tickets.user_id'] = $user_id;
            
        }
        
        $this->db->select('tickets.ticket_id,tickets.user_id,tickets.subject,tickets.status,tickets.important,users.username');
        $this->db->from($this->table);
        $this->db->join('users', 'tickets.user_id=users.user_id', 'left');
        
        if ( $where ) {
            
            $this->db->where($where);
            
        }
        
        if ( $type === 'important' ) {
            
            $this->db->like('tickets.important', 1);
            
        }
        
        if ( $type === 'unanswered' ) {
            
            $this->db->like('tickets.status', 1);
            
        }
        
        if ( $limit ) {
            
            $this->db->limit($limit, $start);
        
        }
        
        $this->db->order_by('tickets.ticket_id', 'desc');
        $query = $this->db->get();
        
        // If $limit is null will return number of rows
        if ( !$limit ) {
            
            return $query->num_rows();
            
        }
        
        if ( $query->num_rows() > 0 ) {
            
            return $query->result_array();
            
        } else {
            
            return false;
            
        }
        
    }
    
    /**
     * The public method get_metas gets ticket's meta
     *
     * @param integer $ticket_id contains the ticket's ID
     * 
     * @return object with all tickets or false
     */
    public function get_metas( $ticket_id ) {
        
        $this->db->select('tickets_meta.created,tickets_meta.body,users.user_id,users.first_name,users.last_name,users.username,users.email');
        $this->db->from('tickets_meta');
        $this->db->join('users', 'tickets_meta.user_id=users.user_id', 'left');
        $this->db->where('tickets_meta.ticket_id', $ticket_id);
        $this->db->order_by('tickets_meta.meta_id', 'desc');
        $query = $this->db->get();
        
        if ( $query->num_rows() > 0 ) {
            
            $results = $query->result();
            
            $all_replies = array();
            
            foreach ( $results as $result ) {
                
                $all_replies[] = array(
                    'created' => $result->created,
                    'body' => nl2br($result->body),
                    'first_name' => ($result->first_name)?$result->first_name:'',
                    'last_name' => ($result->last_name)?$result->last_name:'',
                    'username' => $result->username,
                    'avatar' => 'https://www.gravatar.com/avatar/' . md5($result->email)
                );
                
            }
            
            return $all_replies;
            
        } else {
            
            return false;
            
        }
        
    }
    
    /**
     * The public method ticket_update updates a ticket's columns
     *
     * @param integer $ticket_id contains the ticket_id
     * @param string $key contains the table's column
     * @param string $value contains the column's value
     * 
     * @return boolean true or false
     */
    public function ticket_update( $ticket_id, $key, $value ) {
        
        // Set data
        $data = array(
            $key => $value
        );
        
        $this->db->where('ticket_id', $ticket_id);
        $this->db->update($this->table, $data);
        
        if ( $this->db->affected_rows() ) {
            
            return true;
            
        } else {
            
            return false;
            
        }
        
    }    
    
    /**
     * The public method get_ticket gets ticket's data
     *
     * @param integer $user_id contains the user's id
     * @param integer $ticket_id contains the ticket_id
     * 
     * @return array with the ticket or boolean false
     */
    public function get_ticket( $user_id=0, $ticket_id ) {
        
        // First we check if the ticket exists
        $this->db->select('*');
        $this->db->from($this->table);
        
        if( $user_id ) {
            
            $this->db->where(
                    
                array(
                    'user_id' => $user_id,
                    'ticket_id' => $ticket_id
                )
                    
            );
            
        } else {
            
            $this->db->where(
                    
                array(
                    'ticket_id' => $ticket_id
                )
                    
            );
            
        }
        
        $query = $this->db->get();
        
        if ( $query->num_rows() > 0 ) {

            return $query->result_array();
            
        } else {
            
            return false;
            
        }
        
    }
    
    /**
     * The public method last_reply gets last reply
     *
     * @param integer $user_id contains the user's id
     * @param integer $ticket_id contains the ticket_id
     * 
     * @return string with time or false
     */
    public function last_reply( $user_id, $ticket_id ) {
        
        // First we check if the ticket exists
        $this->db->select('*');
        $this->db->from('tickets_meta');
        $this->db->where(['user_id' => $user_id, 'ticket_id' => $ticket_id]);
        $this->db->order_by('meta_id', 'desc');
        $this->db->limit(1);
        $query = $this->db->get();
        
        if ( $query->num_rows() > 0 ) {
            
            // Then will be extracted the ticket's data
            $result = $query->result();
            return $result[0]->created;
            
        } else {
            
            return false;
            
        }
        
    }
    
    /**
     * The public method last_ticket gets the last ticket
     *
     * @param integer $user_id contains the user's id
     * 
     * @return string with time or false
     */
    public function last_ticket( $user_id ) {
        
        // First we check if the ticket exists
        $this->db->select('*');
        $this->db->from($this->table);
        $this->db->where(['user_id' => $user_id]);
        $this->db->order_by('ticket_id', 'desc');
        $this->db->limit(1);
        $query = $this->db->get();
        
        if ( $query->num_rows() > 0 ) {
            
            // Then will be extracted the ticket's data
            $result = $query->result();
            
            return $result[0]->created;
            
        } else {
            
            return false;
            
        }
        
    }
    
}

/* End of file Tickets_model.php */