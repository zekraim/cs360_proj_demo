<?php
session_start();
// load the database
include("connection.php");
include("functions.php");
if ($_SERVER['REQUEST_METHOD'] == "POST") { // if new data has been posted
    // user has posted intial F to store
    if (isset($_POST['dependencies'])) {
        $clear_query = "truncate table inputfds";
        mysqli_query($con, $clear_query);
        $clear_query = "truncate table closure_solution";
        mysqli_query($con, $clear_query);
        // receive and store form input
        $chars = str_split(strtoupper($_POST['dependencies']));
        //process input into dependencies and store in $dep
        $dep = parse_input_FD($chars);
        //store dependencies from $dep into inputfds
        for ($i = 0; $i < sizeof($dep[0]); $i++) {
            $fd_left = "";
            $fd_right = "";
            foreach ($dep[0][$i] as $char) {
                $fd_left = $fd_left . $char;
            }
            foreach ($dep[1][$i] as $char) {
                $fd_right = $fd_right . $char;
            }
            $query = "insert into inputfds (FD_LEFT,FD_RIGHT) values ('$fd_left','$fd_right')";
            mysqli_query($con, $query);
        }
        // store the attributes
        $relations = store_attributes($chars);
        // grab from table
        $query_input = "select * from inputfds";
        $result_input = mysqli_query($con, $query_input);
        $input_fds = $result_input->fetch_all(MYSQLI_ASSOC);
        
    }
    
    // user has posted attribute to calculate
    if (isset($_POST['calc_attr'])) {
        // grab table data
        $query_input = "select * from inputfds";
        $result_input = mysqli_query($con, $query_input);
        $input_fds = $result_input->fetch_all(MYSQLI_ASSOC);
        // create $dep to use to calculate attributes from table data
        $dep = parse_input_from_table($input_fds);
        // clear calculated attributes from table
        // clear old steps from steps table
        $clear_calc_query = "truncate table closure_solution";
        mysqli_query($con, $clear_calc_query);
        $clear_steps_query = "truncate table student_steps";
        mysqli_query($con, $clear_steps_query);
        // put attribute to be calculated into table
        $attr = $_POST['calc_attr'];
        if (ctype_alpha($attr)){
                    $attr = strtoupper($_POST['calc_attr']);
                    $closure = calcattrclosure($dep, str_split($attr));
                    $closure = implode($closure);
                    $query = "insert into closure_solution (ATTR,CLOSURE) values ('$attr','$closure')";
                    mysqli_query($con, $query);
                    echo $attr;
                    echo $closure;
        }
    }
    
    // add spot for user entering their own FD stuff for closures here:
    
    
    // can add a clear button at the top of the page at somepoint to clear all table data
}

$query_input = "select * from inputfds";
$result_input = mysqli_query($con, $query_input);
$input_fds = $result_input->fetch_all(MYSQLI_ASSOC);
$query_sol = "select * from closure_solution";
$result_sol = mysqli_query($con, $query_sol);
$closure_sols = $result_sol->fetch_all(MYSQLI_ASSOC);
$query_steps = "select * from student_steps";
$result_steps = mysqli_query($con, $query_steps);
$student_steps = $result_steps->fetch_all(MYSQLI_ASSOC);
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

            <br><br>
            <h2>Input FDs</h2>
            <table border="1">
                <tr>
                    <th>FD Number</th>
                    <th>Left</th>
                    <th>Right</th>
                </tr>
                <?php foreach ($input_fds as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['fd_id']) ?></td>
                        <td><?= htmlspecialchars($row['FD_LEFT']) ?></td>
                        <td><?= htmlspecialchars($row['FD_RIGHT']) ?></td>
                    </tr>
                <?php endforeach ?>
            </table>

            <br><br>
            <!-- student chooses a set of attributes to derive a closure of here :: only after input FDs given-->
            <form action="attclose.php" method="post">
                <label for="fname">Attribute Set to Computer Closure Of:</label><br>
                <input type="text" id="fname" name="calc_attr" class="textinput" placeholder="e.g. AB"><br>
                <input type="submit" value="Submit">
            </form> 
            
            <!-- display student solution steps here-->
            <h2>Solution Steps</h2>
            <table border="1">
                <tr>
                    <th>FD Number</th>
                    <th>Left</th>
                    <th>Right</th>
                    <th>Next</th>
                </tr>
                <?php foreach ($student_steps as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['STEP_ID']) ?></td>
                        <td><?= htmlspecialchars($row['FD_LEFT']) ?></td>
                        <td><?= htmlspecialchars($row['FD_RIGHT']) ?></td>
                        <td><?= htmlspecialchars($row['NEXT']) ?></td>
                        
                    </tr>
                <?php endforeach ?>
            </table>
            <!-- Receive next step here-->
            <form action="attclose.php" method="post">
                <label for="fname">Attribute Set to Computer Closure Of:</label><br>
                <input type="text" id="left" name="step" class="textinput">
                <input type="text" id="right" name="step" class="textinput">
                
                <input type="submit" value="Submit">
            </form> 
            

            <br><br>
            <h2>Attribute Closures on R</h2>
            <table border="1">
                <tr>
                    <th>Attribute</th>
                    <th>Closure</th>
                </tr>
                <?php foreach ($closure_sols as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['ATTR']) ?></td>
                        <td><?= htmlspecialchars($row['CLOSURE']) ?></td>
                    </tr>
                <?php endforeach ?>
            </table>
        </div>

    </body>
</html>