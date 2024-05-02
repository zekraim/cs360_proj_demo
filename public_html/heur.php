<!DOCTYPE html>

<html>
    <head>
        <title>Home</title>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="style.css"/>
    </head>
    <body>
        <div class="topnav">
            <a href="index.html">Home</a>
            <a href="attclose.php">Attribute Closure</a>
            <a href="exhaust.php">Exhaustive</a>
            <a class="active" href="heur.html">Heuristic</a>
            <a href="elim.html">Elimination</a>
        </div>

        <div>
            <h1>Heuristic Method</h1>
            
            <p>
                Enter your functional dependencies in the following manner: "A,B,...,C,D>W,X,...,Y,Z:". Capitals do not matter. If you deviate from this 
                format the system will break. {A,B,...,C,D} are the determinant attributes, {W,X,...,Y,Z} are the dependent attributes of each
                functional dependency. The colon separates functional dependencies.
            </p>
            
            <p>
                The Heuristic method will work by splitting all the attributes into three sets. Then it will generate all subsets of two of the sets and calculate
                the attribute closures of these two subsets. The attribute closure that contains all attributes and belongs to the smallest key is the candidate key.
                The other keys that have attribute closures that contain all attributes are superkeys. The other subsets are not keys.
            </p>
            
            <form action="heur.php" method="GET">
                <label for="fname">Input Functional Dependency Set:</label><br>
                <input type="text" id="fname" name="dependencies" class="textinput" placeholder="e.g. a,b>c,d:e>f:"><br>
                <input type="submit" value="Submit">
            </form> 
        </div>

    </body>
</html>