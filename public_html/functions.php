<?php
    
function parse_input_FD($chars)
{
    $left = array();
    $right = array();
    $dep = array($left, $right);
    
    $i=0; // index for current character in $chars
    // assume starting on left side
    while ($i<count($chars)){
        $char = $chars[$i];
        $temp = array();
        // on the left side
        while ($char != ">"){ // for now using != just cuz easier, might be better to use !== later to protect for only good strings
            if ($char != ","){
                array_push($temp, $char);
            }
            $i++;
            $char = $chars[$i];
        }
        array_push($dep[0], $temp);
        $i++;
        $char = $chars[$i];
        $temp = array();
        while ($char != ":"){ // represents end of functional dependency, but also end of the string
            if ($char != ","){
                array_push($temp, $char);
            }
            $i++;
            $char = $chars[$i];
        }
        $i++;
        array_push($dep[1], $temp);
        
    }
    return $dep;
}

function parse_input_from_table($table){
    $dep = array();
    $left = array();
    $right = array();
    foreach ($table as $row){
        array_push($left,str_split(htmlspecialchars($row['FD_LEFT'])));
        array_push($right, str_split(htmlspecialchars($row['FD_RIGHT'])));
    }
    array_push($dep, $left);
    array_push($dep,$right);
    return $dep;
}

function store_attributes($chars){
    $relations = array();
    foreach($chars as $char){
        if ($char != ">" and $char != "," and $char != ":"){
            if (!in_array($char, $relations)){
                array_push($relations, $char);
            }
        }
    }
    return $relations;
}

// generate all possible subsets
    function calcsubsets($attr, &$res, $subset, $index){
        array_push($res, $subset);
        for($j=$index; $j<count($attr); $j++){
            array_push($subset, $attr[$j]);
            calcsubsets($attr, $res, $subset, $j+1);
            array_pop($subset);
        }
    }

    // compute attribute closure of given subset
    function calcattrclosure($dep, $closure){
        $found_new = 1; // set found_new to 1 if found a new attribtue to add, this means to keep searching. Did find a new attribute in line above
        while ($found_new == 1){
            $found_new=0;
            for($z=0; $z<count($dep[0]); $z++){
                $left_side = $dep[0][$z];
                $right_side = $dep[1][$z];
                $j=0;
                while (in_array($left_side[$j], $closure)){ // searching if every attribute in the left side of the FD is in the closure set
                    //echo "j has been increased.".$attr.$d[0][$j];
                    $j++;
                    if ($j==count($left_side)){ // get out of while loop to avoid indexing out of range
                        break;
                    }
                }
                if ($j==count($left_side)){ // every attr in the left side of the FD is in the CS. may be something new to add into the CS.
                    //echo "checking to add in on right side. current attribute: ".$attr;
                    foreach($right_side as $x){
                        if (!in_array($x, $closure)){
                            array_push($closure, $x); // added attribute to CS
                            $found_new = 1; // since something new has been added, now need to keep looping through the whole set of FDs.
                        }
                    }
                }
            }
        }
        return $closure;
    }
    
    // return <0 if length of a less than length of b, 0 if lengths are equal, >0 if length of a more than length of b
    // used for usort compare function later in code
    function cmp($a, $b){
        return count($a)-count($b);
    }