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
    
    // handle the user submitted steps
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
        } else{
            // some sort of php code to javascript to show error an error that not everything is filled out
        }
        
    }
    // can add a clear button at the top of the page at somepoint to clear row data if time permits
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
$student_done_query = "SELECT FD_RIGHT, NEXT FROM student_steps ORDER BY STEP_ID DESC LIMIT 1;";
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

// now here we will handle if the student has selected done.
// steps: 
// 1. check if they are correct
// 2. if they are correct tell them
// 3. if they are incorrect, find(check if exists) incorrect closure step assumptions
// 4. inform user of incorrect ones
// 5. if none exist, assume user didn't make enough assumptions, tell them
// 6. done. tell them they can submit a new R or a new attribute closure to calculate
if ($student_done){ // begin grading process
    $correct_closure = $closure_sols[0]["CLOSURE"];
    if ($student_done_mysqli[0]['FD_RIGHT'] == $correct_closure){
        // correct result, display so
        $correct = true;
    } else {
        $correct = false;
        // incorrect response
        // figure out if made wrong assumption or missing something
        // first run through steps to find if steps includes something it shouldn't
        $over_include_steps = array(); // store the step ID of any steps that included something they shouldn't have
        foreach ($student_steps as $step){
            $contains_extra_attribute = false;
            foreach(str_split($step["FD_RIGHT"]) as $user_char){
                if (!str_contains($correct_closure, $user_char)){ // if a user character is not in the correct closure set
                    $contains_extra_attribute = true;
                }
            }
            if ($contains_extra_attribute == true){
                array_push($over_include_steps, $step["STEP_ID"]);
            }
        }
        // checking for if the user did not include enough attributes in the closure set now
        $invalid_steps = array(); // if the user used an FD in the input FDs that shouldn't have been able to be used
        $under_include_steps = array(); // if the user uses an FD from the input set that can be used, but doesn't include all of the implied attributes from it
        foreach($student_steps as $step){ // checking for invalid uses of an input fd
            $valid_input_fd = true;
            foreach(str_split($input_fds[$step["FD_USED"]-1]["FD_LEFT"]) as $char){
                if (!str_contains($correct_closure, $char)){ // if the left input character is not in the correct closure
                    $valid_input_fd = false;
                }
            }
            if ($valid_input_fd== false){
                array_push($invalid_steps, $step["STEP_ID"]);
            }
        }
        
        foreach($student_steps as $step){ // checking not using what an input id implies fully
            $contains_few_attributes = false;
            foreach(str_split($input_fds[$step["FD_USED"]-1]["FD_RIGHT"]) as $char){
                if(!str_contains($step["FD_RIGHT"], $char)){
                    $contains_few_attributes = true;
                }
            }
            if ($contains_few_attributes == true){
                array_push($under_include_steps, $step["STEP_ID"]);
            }
        }      
    }
} else {
    $correct = false;
}

// handling all of the showing/hiding of forms and displays
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

$json = json_encode(array($input, $attribute, $steps, $student_done, $correct));
echo "<script> var display_data=$json; </script>";

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
    <body onload="disp_sections(display_data[0], display_data[1], display_data[2], display_data[3], display_data[4])">
        <div class="topnav">
            <a href="index.html">Home</a>
            <a class = "active" href="attclose.php">Attribute Closure</a>
            <a href="exhaust.php">Exhaustive</a>
            <a href="heur.php">Heuristic</a>
            <a href="elim.php">Elimination</a>
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

                <p>Click the "Submit" button to display the dependencies and begin the closure calculation.</p>
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
            <div id="correct_sol_disp">
                <h4>Nice work! You found the correct closure.</h4>
            </div>
            <div id="incorrect_sol_disp">
                <h4>You did not find the correct closure.</h4>
                <p>
                    There are three types of mistakes you could have made. The first mistake is including an attribute
                    that does not belong in the final closure set. 
                    The second mistake is using one of the input FD's when its left-hand side was not a subset of the closure set.
                    The third mistake is not including all attributes in the closure set implied by a input FD's right-hand side.
                    The three lists below show the steps where you made this mistake. If the list is empty, you did not make that type of mistake. 
                </p>
                <h5>Extra attributes: <?php foreach($over_include_steps as $char){echo $char.', ';}?></h5>
                <h5>Invalid input usage: <?php foreach($invalid_steps as $char){echo $char.', ';}?></h5>
                <h5>Left-out attribute: <?php foreach($under_include_steps as $char){echo $char.', ';}?></h5>
                <p>
                    Resubmit the attribute closure to try again when you are ready.
                </p>
            </div>
        </div>
    </body>
</html>