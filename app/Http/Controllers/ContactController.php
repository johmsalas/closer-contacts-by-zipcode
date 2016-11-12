<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use \App\Contact;

class ContactController extends Controller
{
    
    function getByClosestZipCode($zipcodes)
    {
        
        $zipcodes = explode(',', $zipcodes);
        $answer = [];
        
        if( count($zipcodes) == 2 ) {
        
            $contacts = Contact::all()->all();
            $match = $this->_matchClosest($contacts, $zipcodes[0], $zipcodes[1]);
            
            if( ISSET($match['status']) && $match['status'] == 'error' ) {
                $answer = $match;             
            } else {                
                $clasifiedContacts = [];
                $agents = ['A', 'B'];
                foreach($agents as $agent) {
                    foreach($match[$agent] as $contact) {
                        $clasifiedContacts[] = [
                            'agent' => [
                                'id' => $agent
                            ],
                            'contact' => $contact,
                        ];
                    }    
                }                   
                $answer = [
                    'status' => 'success',
                    'data' => $clasifiedContacts
                ];
            }
            
        } else {
            
            $answer = [
                'status' => 'error',
                'message' => 'The service requires 2 zip codes'
            ];
            
        }
        
        return $answer;

    }
    
    function _matchClosest($contacts, $zipCodeA, $zipCodeB) {
        
        $n = sizeof($contacts);
        $APoints = [];
        $BPoints = [];

        foreach($contacts as $contact) {
            $toA = $contact->distanceTo( $zipCodeA );
            $toB = $contact->distanceTo( $zipCodeB );
            
            if($toA == -1 || $toB == -1) {
                return [
                    'status' => 'error',
                    'message' => 'zip code not found'
                ];
                // TODO: Throw error instead of returning value
            }
            
            $contact->_trend = $toA - $toB;
        }

        // Sort the points according to the treding
        usort($contacts, function($m, $n)
        {
            if($m->_trend == $n->_trend) {
                return ($this->_hasTheLongestDistance($m,$n)==$m)?1:-1;
            } else {
                return ($m->_trend - $n->_trend);
            }
        });

        // Assign the points to A or B according
        // to their location inside the array of points
        $middle = floor($n/2);
        if($n % 2 == 0) {
            $APoints = array_slice($contacts, 0, $middle);
            $BPoints = array_slice($contacts, $middle);
        } else {
            if(n > 1) {
                $APoints = array_slice($contacts, 0, $middle);
                $BPoints = array_slice($contacts, $middle);
            }

            // If the number of contacts is odd, the contact in the
            //   middle is assigned to its closest agent
            $contact = $contacts[$middle];
            if($contact->_trend < 0) {
                $APoints[] = $contact;
            } else {
                $BPoints[] = $contact;
            }
        }

        return [
          'A' => $APoints,
          'B' => $BPoints
        ];
        
    }

    function _hasTheLongestDistance($m, $n) {

        $maxDistance;
        $_obj = $m;
        $maxDistance = max($m->x, $n->x);

        if($maxDistance < $n->x) {
            $_obj = $n;
            $maxDistance = $n->x;
        }
        if($maxDistance < $n->y) {
            $_obj = $n;
            $maxDistance = $n->y;
        }

        return $_obj;

    }
    
}
