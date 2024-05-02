<?php
    session_start();
    // load the database
    include("connection.php");
    include("functions.php");
    
    $tree_done = false;
    $feedback = false;
    if ($_SERVER['REQUEST_METHOD'] == "POST") { // if new data has been posted
        // user has posted intial F to store
        if (isset($_POST['dependencies'])) {
            $clear_query = "truncate table inputfds";
            mysqli_query($con, $clear_query);
            $clear_query = "truncate table closure_solution";
            mysqli_query($con, $clear_query);
            $clear_steps_query = "truncate table elim_tree";
            mysqli_query($con, $clear_steps_query);
            $clear_candidates_query = "truncate table elim_candidates";
            mysqli_query($con, $clear_candidates_query);


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
            $relations_implode = implode($relations); // used to push initial node onto tree
            $query_first = "insert into elim_tree (ID,ATTR,LEAF) values ('1','$relations_implode','0')";
            mysqli_query($con, $query_first);
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
        if (isset($_POST['submit_step'])){
            if (
                isset($_POST['id']) &&
                isset($_POST['attr']) &&
                isset($_POST['parent']) &&
                isset($_POST['just']) &&
                isset($_POST['leaf'])
            ){
                $id = strtoupper($_POST['id']);
                $attr = strtoupper($_POST['attr']);
                $parent = strtoupper($_POST['parent']);
                $just = strtoupper($_POST['just']);
                $leaf = strtoupper($_POST['leaf']);
                
                $query = "insert into elim_tree (ID,ATTR,PARENT,JUST,LEAF) values ('$id','$attr','$parent','$just','$leaf')";
                mysqli_query($con, $query);
            } else{
                // some sort of php code to javascript to show error an error that not everything is filled out
            }
        }
        if (isset($_POST['submit_tree'])){
            $tree_done = true;
            // grab leafs
            $query_leafs = "select ATTR from elim_tree where LEAF = '1'";
            $result_leafs = mysqli_query($con, $query_leafs);
            $leafs = $result_leafs->fetch_all(MYSQLI_ASSOC);
        }
        if (isset($_POST['submit_candidates'])){
            $feedback = true;
            $user_candidates = explode(",", $_POST['candidates']);
            foreach($user_candidates as $ck){
                $query_ck = "insert into elim_candidates (CANDIDATES) values ('$ck')";
                mysqli_query($con, $query_ck);
            }
        }
        
    }
    // store tables as php variables
    $query_input = "select * from inputfds";
    $result_input = mysqli_query($con, $query_input);
    $input_fds = $result_input->fetch_all(MYSQLI_ASSOC);
    $query_sol = "select * from closure_solution";
    $result_sol = mysqli_query($con, $query_sol);
    $closure_sols = $result_sol->fetch_all(MYSQLI_ASSOC);
    $query_steps = "select * from elim_tree";
    $result_steps = mysqli_query($con, $query_steps);
    $user_tree = $result_steps->fetch_all(MYSQLI_ASSOC);
    $query_candidates = "select * from elim_candidates";
    $result_candidates = mysqli_query($con, $query_candidates);
    $user_candidates = $result_candidates->fetch_all(MYSQLI_ASSOC);
    
    //handling display of divs
    $input=false;
    $tree_disp = false;
    $tree_form = false;
    $candidates_form = false;
    $candidates_disp = false;
    $feedback_correct = false;
    $feedback_incorrect = false;
    if(isset($input_fds[0])){
        $input = true;
        $tree_disp = true;
        $tree_form = true;
    }
    if($tree_done==true){
        $tree_form = false;
        $candidates_form = true;
    }
    if($feedback){
        $tree_form = false;
        $candidates_form = false;
        $candidates_disp = true;
    }
    
    // grading begins
    $real_candidates = array();
    $attribute_query = "SELECT * FROM closure_solution ORDER BY LENGTH(ATTR) DESC LIMIT 1"; // grab relation set
    $result_attributes = mysqli_query($con, $attribute_query);
    $relation = $result_attributes->fetch_all(MYSQLI_ASSOC)[0]['ATTR'];
    $candidate_key_length = 20;
    // first find the length of any candidate keys -- then go count candidate/super keys
    foreach(array_slice($closure_sols, 1) as $closure){
        if(strlen($closure['CLOSURE'])==strlen($relation)){ // is a key
            if(strlen($closure['ATTR'])<$candidate_key_length){
                $candidate_key_length = strlen($closure['ATTR']);
            }
        }
    }
    foreach(array_slice($closure_sols,1) as $closure){
        if($closure['CLOSURE']==$relation){ // is a key
            if(strlen($closure['ATTR'])==$candidate_key_length){ // superkey
                array_push($real_candidates, $closure['ATTR']);
            }
        }
    }
    $real_candidate_count = count($real_candidates);
    $user_candidates_string_arr = array();
    foreach($user_candidates as $uck){
        array_push($user_candidates_string_arr, $uck['CANDIDATES']);
    }
    $correct_user_candidates = array_intersect($real_candidates, $user_candidates_string_arr);
    if($feedback==true){
        if($real_candidate_count == count($correct_user_candidates)){
            $feedback_correct = true;
            $feedback_incorrect = false;
        } else {
            $feedback_correct = false;
            $feedback_incorrect = true;
        }
    }
    
    $json = json_encode(array($input, $tree_disp, $tree_form, $candidates_form, $candidates_disp, $feedback_correct, $feedback_incorrect));
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
        <script src="elim_funcs.js"></script>
    </head>
    <body>
        <div class="topnav">
            <a href="index.html">Home</a>
            <a href="attclose.php">Attribute Closure</a>
            <a href="exhaust.php">Exhaustive</a>
            <a href="heur.php">Heuristic</a>
            <a class="active" href="elim.php">Elimination</a>
        </div>
        
        <h1>Elimination Method</h1>
        <p>
            After you enter in your input functional dependencies, you will create an attribute elimination tree one node at a time.
            The root note, representing the relation set, R, will be automatically created for you.
            Submit one node at a time, be careful to link your parent nodes correctly to create the tree you intend.
            When you do not want a node to have children because no more attributes can be removed, make sure to mark it as a leaf.
            It is recommended to do the calculation with a pencil and paper to keep track of larger trees.
            Once you have constructed your tree, submit the tree. Then the leaf nodes you designated will be displayed.
            Choose from these your candidate keys. Once you have chosen your candidate keys, submit and receive feedback.
            
        </p>
        <div id="input_form">
            <p>
                Enter your functional dependencies in the following manner: "A,B,...,C,D>W,X,...,Y,Z:". Capitals do not matter. If you deviate from this 
                format the system will break. {A,B,...,C,D} are the determinant attributes, {W,X,...,Y,Z} are the dependent attributes of each
                functional dependency. The colon separates functional dependencies.
            </p>
            <form action="elim.php" method="post">
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
        <div id="steps_disp">
                <!-- display student solution steps here-->
                <h2>Solution Steps</h2>
                <table border="1">
                    <tr>
                        <th>Node ID</th>
                        <th>Attributes</th>
                        <th>Parent ID</th>
                        <th>Justification FD</th>
                        <th>Leaf</th>
                    </tr>
                    <?php foreach ($user_tree as $node): ?>
                        <tr>
                            <td><?= htmlspecialchars($node['ID']) ?></td>
                            <td><?= htmlspecialchars($node['ATTR']) ?></td>
                            <td><?= htmlspecialchars($node['PARENT']) ?></td>
                            <td>FD <?= htmlspecialchars($node['JUST']) ?></td>
                            <td><?= htmlspecialchars($node['LEAF']) ?></td>
                        </tr>
                    <?php endforeach ?>
                </table>
            </div>
            <div id="steps_form">
                <!-- Receive next step here-->
                <form action="elim.php" method="post">
                    <label for="fname">Elimination Tree Nodes:</label><br>
                    <label>Node ID:</label>
                    <input type="text" size="10" name="id" class="textinput">
                    <label>ATTR:</label>
                    <input type="text" size="10" name="attr" class="textinput">
                    <label>Parent ID:</label>
                    <input type="text" size="10" name="parent" class="textinput">
                    <label>FD Justification ID:</label>
                    <input type="text" size="2" name="just" class="textinput">
                    <label>Leaf: Y </label>
                    <input type="radio" name="leaf" value="1">
                    <label>N </label>
                    <input type="radio" name="leaf" value="0">
                    <input type="submit" value="Submit Step" name="submit_step">
                </form> 
            </div>
        <div id="done_button">
            <form action="elim.php" method="post">
                <input type="submit" value="Submit Tree" name="submit_tree">
            </form>
        </div>
        <div id="list_candidates_form">
            <p>Your leaf nodes were: <?php foreach($leafs as $l){echo implode($l).", ";}?></p>
            <form action="elim.php" method="post">
                <label>Select your candidate keys. Separate each key by a comma.</label>
                <input type="text" name="candidates" class="textinput">
                <input type="submit" value="Submit Candidates" name="submit_candidates">
            </form>           
        </div>
        <div id="candidates_disp">
            <p>You selected the following candidate keys: <?php foreach($user_candidates as $uck){echo $uck['CANDIDATES'].", ";}?></p>
        </div>
        <div id="correct_feedback">
            <p>Nice work! You found all the correct candidate keys.</p>
        </div>
        <div id="incorrect_feedback">
            <p>
                Incorrect. You found <?php echo count($correct_user_candidates) ?> candidate keys out of <?php echo $real_candidate_count?> candidate keys.
                The candidate keys you found correctly were: <?php foreach($correct_user_candidates as $cuc){echo $cuc.', ';}?>
            </p>
        </div>
        <script>    
            $(document).ready(function(){  
                disp_sections(display_data[0],display_data[1],display_data[2], display_data[3], display_data[4],display_data[5],display_data[6]);
            });
        </script>
    </body>
</html>