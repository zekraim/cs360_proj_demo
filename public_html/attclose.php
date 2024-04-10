<?php
session_start();
    // load the database
    include("connection.php");
    include("functions.php");
        if($_SERVER['REQUEST_METHOD'] == "POST"){ // if new data has been posted
        // once we want to receive user input, can have a larger if statement to check for what type of data is being entered.
        // for now will just clear input
        // in future could have a button to append data aswell incase they forgot but just resetting at the moment is easier
        $clear_query = "truncate table inputfds";
        mysqli_query($con, $clear_query);
        $clear_query = "truncate table closure_solution";
        mysqli_query($con, $clear_query);
        // receive and store form input
	$chars = str_split(strtoupper($_POST['dependencies']));
        //process input into dependencies and store in $dep
        $dep = parse_input_FD($chars);
        //store dependencies from $dep into inputfds
        for($i=0;$i<sizeof($dep[0]); $i++){
            $fd_left = "";
            $fd_right = "";
            foreach($dep[0][$i] as $char){
                $fd_left = $fd_left . $char;
            }
            foreach($dep[1][$i] as $char){
                $fd_right = $fd_right . $char;
            }
            $query = "insert into inputfds (FD_LEFT,FD_RIGHT) values ('$fd_left','$fd_right')";
            mysqli_query($con, $query);
        }
        // store the attributes
        $relations = store_attributes($chars);
        
        
        // compute attribute closures -- store each in the database
        foreach($relations as $attr){
            $closure = calcattrclosure($dep,str_split($attr));
            $closure = implode($closure);
            $query = "insert into closure_solution (ATTR,CLOSURE) values ('$attr','$closure')";
            mysqli_query($con, $query);
        }
        // will add a button to clear all data to run on new relation once allowing for the user to process themselves
    }
    

?>
<!DOCTYPE html>

<html>
    <head>
        <title>Attribute Closure</title>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="style.css"/>
    </head>
    <body>
        <div class="topnav">
            <a href="index.html">Home</a>
            <a class = "active" href="attclose.html">Attribute Closure</a>
            <a href="exhaust.html">Exhaustive</a>
            <a href="heur.html">Heuristic</a>
            <a href="elim.html">Elimination</a>
        </div>

        <div>
            <h1>Attribute Closure Method</h1>
            
            <p>
                Enter your functional dependencies in the following manner: "A,B,...,C,D>W,X,...,Y,Z:". Capitals do not matter. If you deviate from this 
                format the system will break. {A,B,...,C,D} are the determinant attributes, {W,X,...,Y,Z} are the dependent attributes of each
                functional dependency. The colon separates functional dependencies.
            </p>

            <form action="attclose.php" method="post">
                <label for="fname">Input Functional Dependency Set:</label><br>
                <input type="text" id="fname" name="dependencies" class="textinput" placeholder="e.g. a,b>c,d:e>f:"><br>
                <input type="submit" value="Submit">
            </form> 

            <p>Click the "Submit" button to display the dependencies and show the closure set of each element in the relation set. 
                To exit the page you will need to click the back arrow on your browser.".</p>
        </div>

    </body>
</html>