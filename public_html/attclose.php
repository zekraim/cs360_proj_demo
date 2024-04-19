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
        $clear_steps_query = "truncate table student_steps";
        mysqli_query($con, $clear_steps_query);

        
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
        $auto_increment_start = mysqli_num_rows($result_input)+1;
        $input_fds = $result_input->fetch_all(MYSQLI_ASSOC);
        // create $dep to use to calculate attributes from table data
        $dep = parse_input_from_table($input_fds);
        // clear calculated attributes from table
        // clear old steps from steps table
        $clear_calc_query = "truncate table closure_solution";
        mysqli_query($con, $clear_calc_query);
        $clear_steps_query = "truncate table student_steps";
        mysqli_query($con, $clear_steps_query);
        $steps_auto_increment_query = "alter table student_steps AUTO_INCREMENT=$auto_increment_start";
        mysqli_query($con, $steps_auto_increment_query);
        // put attribute to be calculated into table
        $attr = $_POST['calc_attr'];
        if (ctype_alpha($attr)){
                    $attr = strtoupper($_POST['calc_attr']);
                    $closure = calcattrclosure($dep, str_split($attr));
                    $closure = implode($closure);
                    $query = "insert into closure_solution (ATTR,CLOSURE) values ('$attr','$closure')";
                    mysqli_query($con, $query);

        }
    }
    
    // add spot for user entering their own FD stuff for closures here:
    // need to have some sort of condition to trigger stopping inputs when done is selected.
    // then will compare if the attribute closure calculated is correct and display whether or not.
    // if we want to get fancy/have time we can go through each step and try to figure out if one was invalid --- if none invalid, then one missing
    // this honestly might be really difficult to do though, because there are three other pages to set up aswell
    if (isset($_POST['submit_step'])) {
        if (
                isset($_POST['step_right']) &&
                isset($_POST['step_used']) &&
                isset($_POST['next'])
        ){
            $right = strtoupper($_POST['step_right']);
            $used = $_POST['step_used'];
            $next = $_POST['next'];
            $query = "insert into student_steps (FD_RIGHT,FD_USED, NEXT) values ('$right','$used','$next')";
            mysqli_query($con, $query);
            // here is probably where would need to check for if $next=="DONE" and then run some php
        } else{
            // some sort of php code to javascript to show error an error that not everything is filled out
        }
        
    }
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
// handling all of the showing/hiding of forms and displays
$student_done_query = "SELECT NEXT FROM student_steps ORDER BY STEP_ID DESC LIMIT 1;";
$result_student_done = mysqli_query($con, $student_done_query);
$student_done_mysqli = $result_student_done->fetch_all(MYSQLI_ASSOC);
if (isset($student_done_mysqli[0])){
    if($student_done_mysqli[0]['NEXT'] == "DONE"){
        $student_done=true;
    } else{
        $student_done = false;
    }
} else{
    $student_done = false;
}
if (isset($input_fds[0])){
    $input = true;
} else {
    $input = false;
}
if (isset($closure_sols[0])){
    $attribute = true;
} else{
    $attribute = false;
}
if (isset($student_steps[0])){
    $steps = true;
} else {
    $steps = false;
}
$json = json_encode(array($input, $attribute, $steps, $student_done));
echo "<script> var display_data=$json; </script>";

// now here we will handle if the student has selected done.
// steps: 
// 1. check if they are correct
// 2. if they are correct tell them
// 3. if they are incorrect, find(check if exists) incorrect closure step assumptions
// 4. inform user of incorrect ones
// 5. if none exist, assume user didn't make enough assumptions, tell them
// 6. done. tell them they can submit a new R or a new attribute closure to calculate
        
?>
<!DOCTYPE html>

<html>
    <head>
        <title>Attribute Closure</title>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="style.css"/>
        <script src="attclose_funcs.js"></script>
    </head>
    <body onload="disp_sections(display_data[0], display_data[1], display_data[2], display_data[3])">
        <div class="topnav">
            <a href="index.html">Home</a>
            <a class = "active" href="attclose.html">Attribute Closure</a>
            <a href="exhaust.html">Exhaustive</a>
            <a href="heur.html">Heuristic</a>
            <a href="elim.html">Elimination</a>
        </div>

        <div>
            <h1>Attribute Closure Method</h1>
            <div id="input_form">
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
            
            <div id="input_disp">
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
            </div>

            <br><br>
            <!-- student chooses a set of attributes to derive a closure of here :: only after input FDs given-->
            <div id="attr_form">
                <form action="attclose.php" method="post">
                    <label for="fname">Attribute Set to Computer Closure Of:</label><br>
                    <input type="text" id="fname" name="calc_attr" class="textinput" placeholder="e.g. AB"><br>
                    <input type="submit" value="Submit">
                </form>
            </div>
            <div id="attr_disp">
                <h3>Attribute: <?php if(isset($closure_sols[0])){echo $closure_sols[0]['ATTR'];}?></h3>
            </div>
            <div id="steps_disp">
                <!-- display student solution steps here-->
                <h2>Solution Steps</h2>
                <table border="1">
                    <tr>
                        <th>FD Number</th>
                        <th>Attribute</th>
                        <th>Right</th>
                        <th>Used</th>
                        <th>Next</th>
                    </tr>
                    <?php foreach ($student_steps as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['STEP_ID']) ?></td>
                            <td><?php if(isset($closure_sols[0])){echo $closure_sols[0]['ATTR'];} ?></td>
                            <td><?= htmlspecialchars($row['FD_RIGHT']) ?></td>
                            <td>FD <?= htmlspecialchars($row['FD_USED']) ?></td>
                            <td><?= htmlspecialchars($row['NEXT']) ?></td>

                        </tr>
                    <?php endforeach ?>
                </table>
            </div>
            <div id="steps_form">
                <!-- Receive next step here-->
                <form action="attclose.php" method="post">
                    <label for="fname">Closure Calculation Steps:</label><br>
                    <input type="text" size="10" id="right" name="step_right" class="textinput">
                    <input type="text" size="2" id="used" name="step_used" class="textinput">
                    <input type="radio" id="next_continue" name="next" value="CONTINUE">
                    <input type="radio" id="next_continue" name="next" value="DONE">
                    <input type="submit" value="Submit Step" name="submit_step">
                </form> 
            </div>
            <div id="sol_disp">
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
            
        </div>
    </body>
</html>