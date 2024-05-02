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
        $clear_k_sets_query = "truncate table k_sets";
        mysqli_query($con, $clear_k_sets_query);

        
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
        
        // create K sets and fill into k_sets table in db
        // create k sets
        $k_plus = array();
        $k_ques = array();
        $k_minus = array();
        $plus_just = array();
        $ques_just = array();
        $minus_just = array();
        foreach($relations as $attr){
            $in_left = 0;
            $in_right = 0;
            $attr_plus = array();
            $attr_minus = array();
            for($i=0; $i<count($dep[0]); $i++){
                foreach($dep[0][$i] as $x){
                    if ($attr == $x){
                        $in_left = 1;
                        array_push($attr_plus, $i+1);
                    }
                }
                foreach($dep[1][$i] as $y){
                    if ($attr == $y){
                        $in_right = 1;
                        array_push($attr_minus, $i+1);
                    }
                }
            }
            if ($in_left == 0 && $in_right == 1){
                array_push($k_minus, $attr);
                $minus_just = array_unique(array_merge($minus_just, $attr_minus));
            }
            elseif ($in_left == 1 && $in_right == 1){
                array_push($k_ques, $attr);
                $ques_just = array_unique(array_merge($ques_just, $attr_plus, $attr_minus));
            } else{
                array_push($k_plus, $attr);
                $plus_just = array_unique(array_merge($plus_just, $attr_plus));
            }
        }
        // store in db
        $k_plus_str = implode($k_plus);
        $k_ques_str = implode($k_ques);
        $k_minus_str = implode($k_minus);
        $plus_just_str = implode($plus_just);
        $ques_just_str = implode($ques_just);
        $minus_just_str = implode($minus_just);
        $query_plus = "insert into k_sets (SET_TYPE, ORIGIN, ATTR, JUST) values ('K+','REAL','$k_plus_str','$plus_just_str')";
        $query_ques = "insert into k_sets (SET_TYPE, ORIGIN, ATTR, JUST) values ('K?','REAL','$k_ques_str','$ques_just_str')";
        $query_minus = "insert into k_sets (SET_TYPE, ORIGIN, ATTR, JUST) values ('K-','REAL','$k_minus_str','$minus_just_str')";
        mysqli_query($con, $query_plus);
        mysqli_query($con, $query_ques);
        mysqli_query($con, $query_minus);
        
        
                
        
        // compute the closures of each subset of the union set
        // create the union set of K+ and K?
        $union_set = array_merge($k_plus, $k_ques);
        // create a list of all possible combinations of attributes
        $subsets = array(); // store array of all subsets
        $subset = array(); // store individual subsets
        calcsubsets($union_set, $subsets, $subset,0);
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
    if (isset($_POST['submit_k_forms'])){
        if (
                isset($_POST['plus_attr']) && 
                isset($_POST['plus_just']) && 
                isset($_POST['ques_attr']) && 
                isset($_POST['ques_just']) && 
                isset($_POST['minus_attr']) && 
                isset($_POST['minus_just'])
        ) {
            $clear_steps_query = "truncate table student_steps";
            mysqli_query($con, $clear_steps_query);
            $clear_student_k_steps_query = "delete from k_sets where ORIGIN = 'STUDENTS'";
            mysqli_query($con, $clear_student_k_steps_query);
            $plus_attr = strtoupper($_POST['plus_attr']);
            $plus_just = $_POST['plus_just'];
            $ques_attr = strtoupper($_POST['ques_attr']);
            $ques_just = $_POST['ques_just'];
            $minus_attr = strtoupper($_POST['minus_attr']);
            $minus_just = $_POST['minus_just'];
            $query_plus = "insert into k_sets (SET_TYPE, ORIGIN, ATTR, JUST) values ('K+','STUDENT','$plus_attr','$plus_just')";
            $query_ques = "insert into k_sets (SET_TYPE, ORIGIN, ATTR, JUST) values ('K?','STUDENT','$ques_attr','$ques_just')";
            $query_minus = "insert into k_sets (SET_TYPE, ORIGIN, ATTR, JUST) values ('K-','STUDENT','$minus_attr','$minus_just')";
            mysqli_query($con, $query_plus);
            mysqli_query($con, $query_ques);
            mysqli_query($con, $query_minus);
                    
        } else {
            // some sort of error that things are not filled out
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
$k_sets_student_query = "SELECT * FROM k_sets WHERE ORIGIN='STUDENT'";
$result_k_student = mysqli_query($con, $k_sets_student_query);
$k_sets_student = $result_k_student->fetch_all(MYSQLI_ASSOC);
$k_sets_real_query = "SELECT * FROM k_sets WHERE ORIGIN='REAL'";
$result_k_real = mysqli_query($con, $k_sets_real_query);
$k_sets_real = $result_k_real->fetch_all(MYSQLI_ASSOC);

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
    $attribute_query = "SELECT * FROM closure_solution ORDER BY LENGTH(CLOSURE) DESC LIMIT 1"; // grab relation set
    $result_attributes = mysqli_query($con, $attribute_query);
    $relation = $result_attributes->fetch_all(MYSQLI_ASSOC)[0]['CLOSURE'];
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
// assume the basic forms are all false to start
$input = false;
$k_disp = false;
$k_correct = false;
$k_form = false;
if (isset($input_fds[0])){
    $input = true;
    $k_form = true;
}
if(isset($k_sets_student[0])){
    $k_disp = true;
    // now check if student was correct or not
    if(
        $k_sets_student[0]['ATTR'] == $k_sets_real[0]['ATTR'] &&
        $k_sets_student[1]['ATTR'] == $k_sets_real[1]['ATTR'] &&
        $k_sets_student[2]['ATTR'] == $k_sets_real[2]['ATTR']
        ){
        $k_correct=true;
        $k_form=false;
    } else{
        $k_correct = false;
    }
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

$json = json_encode(array($input, $steps, $subsets_done, $correct, $alldone, $k_form, $k_disp, $k_correct));
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
        <script src="heur_funcs.js"></script>
    </head>
    <body>
        <div class="topnav">
            <a href="index.html">Home</a>
            <a href="attclose.php">Attribute Closure</a>
            <a href="exhaust.php">Exhaustive</a>
            <a class="active" href="heur.php">Heuristic</a>
            <a href="elim.html">Elimination</a>
        </div>

        <h1>Heuristic Method</h1>
            <div id="input_form">
                <p>
                    The Heuristic method will work by splitting all the attributes into three sets. Then it will generate all subsets of two of the sets and calculate
                    the attribute closures of these two subsets. The attribute closure that contains all attributes and belongs to the smallest key is the candidate key.
                    The other keys that have attribute closures that contain all attributes are superkeys. The other subsets are not keys.
                    
                    You will submit your functional dependencies as described below. Then you will compute each of the three sets.
                </p>
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

                <form action="heur.php" method="post">
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
        <div id="k_forms"> 
            <p>When you enter your forms, list out all the attributes you wish to include in the first box.
            Then list the number of each functional dependency you used to justify your decision for any attribute. 
            Do not include any commas or any other special characters.
            If you are correct, you will be able to begin computing the closures of the K+ and K? sets to find the candidate and superkeys.
            If you are incorrect, you will be notified and provided feedback to reenter the K sets again.</p>
            <form action="heur.php" method="post">
                <label>K+</label>
                <input type="text" size="10" id="plus_attr" name="plus_attr" class="textinput">
                <input type="text" size="10" id="plus_just" name="plus_just" class="textinput">
                <br>
                <label>K?</label>
                <input type="text" size="10" id="ques_attr" name="ques_attr" class="textinput">
                <input type="text" size="10" id="ques_just" name="ques_just" class="textinput">
                <br>
                <label>K-</label>
                <input type="text" size="10" id="minus_attr" name="minus_attr" class="textinput">
                <input type="text" size="10" id="minus_just" name="minus_just" class="textinput">
                <br>
                <input type="submit" value="submit" name="submit_k_forms">
            </form>
        </div>
        <div id="k_disp">
            <h2>K Sets</h2>
            <table border="1">
                <tr>
                    <th>K Set</th>
                    <th>Attributes</th>
                    <th>Justification Functional Dependencies</th>
                </tr>
                <tr>
                    <td>K+</td>
                    <td><?php echo $k_sets_student[0]['ATTR'];?></td>
                    <td><?php echo $k_sets_student[0]['JUST']; ?></td>
                </tr>
                <tr>
                    <td>K?</td>
                    <td><?php echo $k_sets_student[1]['ATTR']; ?></td>
                    <td><?php echo $k_sets_student[1]['JUST']; ?></td>
                </tr>
                <tr>
                    <td>K-</td>
                    <td><?php echo $k_sets_student[2]['ATTR']; ?></td>
                    <td><?php echo $k_sets_student[2]['JUST']; ?></td>
                </tr>
            </table>
        </div>
        <div id="k_feedback_correct">
            <h3>You entered the correct K sets. You may now compute the closures of the K+ and K? union subsets.</h3>
        </div>
        <div id="k_feedback_incorrect">
            <h3>You computed the incorrect K sets.</h3>
            <p>
                You either failed to use or misused the functional dependencies for each set below.
                You also may have used a functional dependency correctly and incorrectly at the same time, in which case it will not appear in the corresponding list below.
                There is no guarantee that all of the sets are incorrect.
                <br>
                <b>K+</b>:<?php echo implode(array_merge(
                        array_diff(str_split($k_sets_student[0]['JUST']),str_split($k_sets_real[0]['JUST'])), 
                        array_diff(str_split($k_sets_real[0]['JUST']), str_split($k_sets_student[0]['JUST']))))?>
                <br>
                <b>K?</b>:<?php echo implode(array_merge(
                        array_diff(str_split($k_sets_student[1]['JUST']),str_split($k_sets_real[1]['JUST'])), 
                        array_diff(str_split($k_sets_real[1]['JUST']), str_split($k_sets_student[1]['JUST']))))?>
                <br>
                <b>K-</b>:<?php echo implode(array_merge(
                        array_diff(str_split($k_sets_student[2]['JUST']),str_split($k_sets_real[2]['JUST'])), 
                        array_diff(str_split($k_sets_real[2]['JUST']), str_split($k_sets_student[2]['JUST']))))?>
            </p>
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
            <div id="attr_disp_<?php echo $attr;?>">
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
                <form action="heur.php" method="post">
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
                disp_sections(display_data[0],display_data[1],display_data[2], display_data[3], display_data[4],display_data[5],display_data[6],display_data[7]);
            });
        </script>
    </body>
</html>