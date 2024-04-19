
// big hiding decision tree function
function disp_sections(input, attribute, steps, done, correct){
    hide_input_disp();
    hide_attr_form();
    hide_attr_disp();
    hide_steps_form();
    hide_steps_disp();
    hide_sol_disp();
    hide_correct_sol_disp();
    hide_incorrect_sol_disp();
    if (input){ // input has been given by user, so show attribute form and input
        show_input_disp();
        show_attr_form();
        if (attribute){ // attribute has been given, so show the attr_disp and steps form
            show_attr_disp();
            show_steps_form();
            if (steps){// a step has been given so show the steps
                show_steps_disp();
                if (done){ // student is done giving steps, so show correct answer and hide the steps form
                    hide_steps_form();
                    show_sol_disp();
                    if (correct){ // tell student they were correct
                        show_correct_sol_disp();
                    } else { // tell student they were incorrect
                        show_incorrect_sol_disp();
                    }
                }
            }
        }
    }
}
function test_func(){
    alert("inside test_func");
}
// hide element functions
function hide_input_form(){
    document.getElementById('input_form').hidden = true;
}
function hide_input_disp(){
    document.getElementById('input_disp').hidden = true;
}
function hide_attr_form(){
    document.getElementById('attr_form').hidden = true;
}
function hide_attr_disp(){
    document.getElementById('attr_disp').hidden = true;
}
function hide_steps_form(){
    document.getElementById('steps_form').hidden = true;
}
function hide_steps_disp(){
    document.getElementById('steps_disp').hidden = true;
}
function hide_sol_disp(){
    document.getElementById('sol_disp').hidden = true;
}
function hide_correct_sol_disp(){
    document.getElementById('correct_sol_disp').hidden = true;
}
function hide_incorrect_sol_disp(){
    document.getElementById('incorrect_sol_disp').hidden = true;
}

// show functions
function show_input_form(){
    document.getElementById('input_form').hidden = false;
}
function show_input_disp(){
    document.getElementById('input_disp').hidden = false;
}
function show_attr_form(){
    document.getElementById('attr_form').hidden = false;
}
function show_attr_disp(){
    document.getElementById('attr_disp').hidden = false;
}
function show_steps_form(){
    document.getElementById('steps_form').hidden = false;
}
function show_steps_disp(){
    document.getElementById('steps_disp').hidden = false;
}
function show_sol_disp(){
    document.getElementById('sol_disp').hidden = false;
}
function show_correct_sol_disp(){
    document.getElementById('correct_sol_disp').hidden = false;
}
function show_incorrect_sol_disp(){
    document.getElementById('incorrect_sol_disp').hidden = false;
}
