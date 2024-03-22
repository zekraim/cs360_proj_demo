<html>
<body>
<?php
    // functions
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
    function calcattrclosure($dep, &$closure){
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
    }

    $_GET["dependencies"] = strtoupper($_GET["dependencies"]);
    $chars = str_split($_GET["dependencies"]);

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
    
    // display the functional dependencies entered, one per row
    echo "You entered the following Functional Dependencies: <br>";
    $i=0;
    while ($i<count($dep[0])){
        foreach ($dep[0][$i] as $attribute) {
            echo $attribute;
        }
        echo "   :   ";
        foreach ($dep[1][$i] as $attribute) {
            echo $attribute;
        }
        echo "<br>";
        $i++;
    }
    // store an array with each attribute the user gave
    $relations = array();
    foreach($chars as $char){
        if ($char != ">" and $char != "," and $char != ":"){
            if (!in_array($char, $relations)){
                array_push($relations, $char);
            }
        }
    }
    
    // create a list of all possible combinations of attributes
    $subsets = array(); // store array of all subsets
    $subset = array(); // store individual subsets
    calcsubsets($relations, $subsets, $subset,0);

    // compute attribute closure for each combination
    echo "<br>";
    echo "The closure sets of each subset of attributes in R are as follows: <br>";
    $closures = array();
    foreach($subsets as $sub){ // calculates and displays all of the closure sets of each attribute
        $closure = $sub; // always contains itself
        // search each left side for subset of whats already in $closure
        calcattrclosure($dep, $closure);
        array_push($closures, $closure);
    }  
    // compute length of smallest superkey
    $smallest = count($relations);
    for($i=0; $i<count($closures); $i++){
       if (count($closures[$i]) == count($relations)){ // is at least a superkey
            if (count($subsets[$i])<$smallest){
                $smallest = count($subsets[$i]);
            }
        }
    }
    // print out whether a attribute closure is a candidate key, superkey, or none
    for ($i=0; $i<count($subsets); $i++){
        echo "{";
        foreach($subsets[$i] as $y){
            echo $y;
        }
        echo "}<sup>+</sup> = {";
        foreach($closures[$i] as $x){
            echo $x.",";
        }
        echo "}";
        if (count($closures[$i]) == count($relations)){ // is a candidate or superkey
            if (count($subsets[$i]) == $smallest){ // is a candidate key
                echo " --- CANDIDATE KEY<br>";
            } else{ // is a superkey
                echo " --- SUPER KEY<br>";   
            }
        }
        else {
            echo " --- NOT A KEY<br>";
        }
    }

    
?>



</body>
</html>