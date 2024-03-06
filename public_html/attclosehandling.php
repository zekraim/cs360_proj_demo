<html>
<body>
<?php
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
    echo "<br>";
    echo "The closure sets of each attribute in R are as follows: <br>";
    foreach($relations as $attr){ // calculates and displays all of the closure sets of each attribute
        $closure = array($attr); // always contains itself
        // search each left side for subset of whats already in $closure
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
        echo "{".$attr."}<sup>+</sup> = {";
        foreach($closure as $x){
            echo $x.",";
        }
        echo "}<br>";
    }  
    
?>



</body>
</html>