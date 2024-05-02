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
        
        // compute the closures of each subset of the relations
        // create a list of all possible combinations of attributes
        $subsets = array(); // store array of all subsets
        $subset = array(); // store individual subsets
        calcsubsets($relations, $subsets, $subset,0);
        usort($subsets, "cmp");

        // compute attribute closure for each combination
        $closures = array();
        foreach($subsets as $sub){ // calculates and displays all of the closure sets of each attribute
            $closure = $sub; // always contains itself
            // search each left side for subset of whats already in $closure
            $closure = calcattrclosure($dep, $closure);
            array_push($closures, $closure);
            $closure = implode($closure);
            $sub_str=implode($sub);
            $query = "insert into closure_solution (ATTR,CLOSURE) values ('$sub_str','$closure')";
            mysqli_query($con, $query);
        }  
    }
    
    if (isset($_POST['submit_step'])) {
        if (
                isset($_POST['step_left']) &&
                isset($_POST['step_right']) &&
                isset($_POST['step_used']) &&
                isset($_POST['next'])
        ){
            $left = strtoupper($_POST['step_left']);
            $right = strtoupper($_POST['step_right']);
            $used = $_POST['step_used'];
            $next = $_POST['next'];
            $query = "insert into student_steps (FD_LEFT,FD_RIGHT,FD_USED, NEXT) values ('$left','$right','$used','$next')";
            mysqli_query($con, $query);
        } else{
            // some sort of php code to javascript to show error an error that not everything is filled out
        }
        
    }
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

$alldone = true; // set to false if a single subset fails
$subsets_done = array(); // store whether each subset is done here -- if done, then stop displaying form and display feedback
$to_grade = array(); // just store which subsets have been finished so they can be easily graded
foreach(array_slice($closure_sols,1) as $closure){ // check each subset
    $subset_done=false;
    foreach($student_steps as $step){ // check every step and if it contains the closure check if it is done
        if($step['FD_LEFT']==$closure['ATTR']){
            if($step['NEXT']=='NON' || $step['NEXT']=='SUPER' || $step['NEXT']=='CANDIDATE'){
                $subset_done=true;
                array_push($to_grade, $step);
            }
        }
    }
    if($subset_done == false){
        $alldone=false;
    }
    array_push($subsets_done, array($closure['ATTR'], $subset_done));
}

if($alldone == true){ // count number of correct superkeys and candidate keys
    $query_super = "select count(case when NEXT='SUPER' then 1 else null END) as superkeys from student_steps";
    $result_super = mysqli_query($con, $query_super);
    $user_super_count = $result_super->fetch_all(MYSQLI_ASSOC)[0]['superkeys']; // maybe will need to adjust
    $query_candidate = "select count(case when NEXT='CANDIDATE' then 1 else null END) as candidatekeys from student_steps";
    $result_candidate = mysqli_query($con, $query_candidate);
    $user_candidate_count = $result_candidate->fetch_all(MYSQLI_ASSOC)[0]['candidatekeys']; // maybe will need to adjust
    $real_candidate_count = 0;
    $real_super_count = 0;
    $attribute_query = "SELECT * FROM closure_solution ORDER BY LENGTH(ATTR) DESC LIMIT 1"; // grab relation set
    $result_attributes = mysqli_query($con, $attribute_query);
    $relation = $result_attributes->fetch_all(MYSQLI_ASSOC)[0]['ATTR'];
    $candidate_key_length = 20;
    // first find the length of any candidate keys -- then go count candidate/super keys
    foreach(array_slice($closure_sols, 1) as $closure){
        if($closure['CLOSURE']==$relation){ // is a key
            if(strlen($closure['ATTR'])<$candidate_key_length){
                $candidate_key_length = strlen($closure['ATTR']);
            }
        }
    }
    foreach(array_slice($closure_sols,1) as $closure){
        if($closure['CLOSURE']==$relation){ // is a key
            if(strlen($closure['ATTR'])>$candidate_key_length){ // superkey
                $real_super_count = $real_super_count + 1;
            } else {
                $real_candidate_count = $real_candidate_count + 1;
            }
        }
    }
    
}

// store feedback for each closure calculation
$feedback = array();
$correct = array();
foreach($to_grade as $answer){
    // gather the steps used to generate the answer
    $subset = $answer['FD_LEFT'];
    $query_solution = "select * from student_steps where FD_LEFT = '$subset'";
    $solution_result = mysqli_query($con, $query_solution);
    $solution = $solution_result->fetch_all(MYSQLI_ASSOC);
    // grab the correct closure
    $query_correct = "select CLOSURE from closure_solution where ATTR = '$subset'";
    $correct_result = mysqli_query($con, $query_correct);
    $correct_closure = $correct_result->fetch_all(MYSQLI_ASSOC)[0]['CLOSURE'];
    // check if user was correct
    if($correct_closure == $answer['FD_RIGHT']){ // student was correct
        array_push($correct, array($subset, true));
    } else {
        // grade the steps
        // overinclusion
        $over_include_steps = array(); // store the step ID of any steps that included something they shouldn't have
        foreach ($solution as $step){
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
        //underinclusion
        $under_include_steps = array(); // if the user uses an FD from the input set that can be used, but doesn't include all of the implied attributes from it
        foreach($solution as $step){ // checking not using what an input id implies fully
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
        //untenability
        $invalid_steps = array(); // if the user used an FD in the input FDs that shouldn't have been able to be used
        foreach($solution as $step){ // checking for invalid uses of an input fd
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
        $feedback[$subset] = array($over_include_steps,$under_include_steps, $invalid_steps);
        array_push($correct, array($subset, false));
    }
    
  
    
}


// with alldone can count correct superkeys, candidate keys

// handling all of the showing/hiding of forms and displays
if (isset($input_fds[0])){
    $input = true;
} else {
    $input = false;
}
// need to store whether steps have been entered or not for every single attribute subsret on R
$steps = array();
foreach(array_slice($closure_sols, 1) as $closure){
    $step_exist = false;
    foreach($student_steps as $step){
        if ($step['FD_LEFT']==$closure['ATTR']){
            $step_exist = true;
        }
    }
    array_push($steps,array($closure['ATTR'], $step_exist));
}
// need to store whether the student is done

$json = json_encode(array($input, $steps, $subsets_done, $correct, $alldone));
echo "<script> var display_data=$json; </script>";


?>

<!DOCTYPE html>
<html>
    <head>
        <title>Home</title>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="style.css"/>
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
        <script src="exhaust_funcs.js"></script>
        
    </head>
    <body>
        <div class="topnav">
            <a href="index.html">Home</a>
            <a href="attclose.php">Attribute Closure</a>
            <a class ="active" href="exhaust.php">Exhaustive</a>
            <a href="heur.php">Heuristic</a>
            <a href="elim.html">Elimination</a>
        </div>
        
        <h1>Exhaustive Method</h1>
            <div id="input_form">
                <p>
                You will be shown feedback for each closure you compute.
                There are three types of mistakes you could have made for each closure computation. The first mistake is including an attribute
                that does not belong in the final closure set. 
                The second mistake is using one of the input FD's when its left-hand side was not a subset of the closure set.
                The third mistake is not including all attributes in the closure set implied by a input FD's right-hand side.
                The three lists below each closure computation show the steps where you made this mistake. If the list is empty, you did not make that type of mistake. 
                </p>
                <p>
                    Enter your functional dependencies in the following manner: "A,B,...,C,D>W,X,...,Y,Z:". Capitals do not matter. If you deviate from this 
                    format the system will break. {A,B,...,C,D} are the determinant attributes, {W,X,...,Y,Z} are the dependent attributes of each
                    functional dependency. The colon separates functional dependencies.
                </p>

                <form action="exhaust.php" method="post">
                    <label for="fname">Input Functional Dependency Set:</label><br>
                    <input type="text" id="fname" name="dependencies" class="textinput" placeholder="e.g. a,b>c,d:e>f:"><br>
                    <input type="submit" value="Submit">
                </form> 

                <p>Click the "Submit" button to display the dependencies and begin searching for candidate keys.</p>
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
        <?php 
        $i=1;
        foreach(array_slice($closure_sols, 1) as $closure_sol): 
            $attr = $closure_sol['ATTR'];
            $current_steps = array();
            foreach($student_steps as $row){
                if($row['FD_LEFT']==$attr){
                    array_push($current_steps, $row);
                }
            }
        ?>
            <div id="attr_disp">
                <h3>Attribute: <?php echo $attr;?></h3>
            </div>
            <div id="steps_disp_<?php echo $attr;?>">
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
                    <?php foreach ($current_steps as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['STEP_ID']) ?></td>
                            <td><?php if(isset($closure_sols[$i])){echo $closure_sols[$i]['ATTR'];}?></td>
                            <td><?= htmlspecialchars($row['FD_RIGHT']) ?></td>
                            <td>FD <?= htmlspecialchars($row['FD_USED']) ?></td>
                            <td><?= htmlspecialchars($row['NEXT']) ?></td>
                        </tr>
                    <?php endforeach ?>
                </table>
            </div>
            <div id="steps_form_<?php echo $attr;?>">
                <!-- Receive next step here-->
                <form action="exhaust.php" method="post">
                    <label for="fname">Closure Calculation Steps:</label><br>
                    <input type="hidden" id="left" name="step_left" class="textinput" value="<?php if(isset($closure_sols[$i])){echo $closure_sols[$i]['ATTR'];}?>">
                    <input type="text" size="10" id="right" name="step_right" class="textinput">
                    <input type="text" size="2" id="used" name="step_used" class="textinput">
                    <label> CONTINUE </label>
                    <input type="radio" id="next_continue" name="next" value="CONTINUE">
                    <label> NON-KEY </label>
                    <input type="radio" id="next_continue" name="next" value="NON">
                    <label> CANDIDATE-KEY </label>
                    <input type="radio" id="next_continue" name="next" value="CANDIDATE">
                    <label> SUPER-KEY </label>
                    <input type="radio" id="next_continue" name="next" value="SUPER">
                    
                    <input type="submit" value="Submit Step" name="submit_step">
                </form> 
            </div>
            <div id="correct_sol_disp_<?php echo $attr;?>">
                <h4>Nice work! You found the correct closure.</h4>
            </div>
            <div id="incorrect_sol_disp_<?php echo $attr;?>">
                <h4>You did not find the correct closure.</h4>
                <h5>Extra attributes: <?php foreach($feedback[$attr][0] as $char){echo $char.', ';}?></h5>
                <h5>Invalid input usage: <?php foreach($feedback[$attr][2] as $char){echo $char.', ';}?></h5>
                <h5>Left-out attribute: <?php foreach($feedback[$attr][1] as $char){echo $char.', ';}?></h5>
            </div>
        <?php 
        $i=$i+1;
        endforeach ?>
        <div id="alldone_feedback">
            <p>
                You got <?php echo $user_candidate_count ?>/<?php echo $real_candidate_count?> candidate keys correct.<br>
                You got <?php echo $user_super_count ?>/<?php echo $real_super_count?> super keys correct.<br>
            </p>
        </div>
        <script>
            
            
            $(document).ready(function(){                
                disp_sections(display_data[0],display_data[1],display_data[2], display_data[3], display_data[4]);
            });
        </script>
    </body>
</html>