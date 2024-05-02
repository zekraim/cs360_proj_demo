function disp_sections(input, steps, done, feedback, alldone){
    hide_input_disp();
    console.log(steps);
    show_input_disp();
    for(let step of steps){
        hide_form('steps_disp_'.concat(step[0]));
    }
    for(let d of done){
        hide_form('incorrect_sol_disp_'.concat(d[0]));
        hide_form('correct_sol_disp_'.concat(d[0]));
    }
    hide_form('alldone_feedback');
    
    if (input){
        show_input_disp();
        for(let step of steps){
            if(step[1]){ // there are steps for the attribute
                show_form('steps_disp_'.concat(step[0]));
            } else { // there are no steps for the attribute
                hide_form('steps_disp_'.concat(step[0]));
            }
        }
        for(let d of done){
            if(d[1]){ // the subset calculation is finished
                hide_form('steps_form_'.concat(d[0]));
            } else{
                show_form('steps_form_'.concat(d[0]));
            }
        }
        for(let f of feedback){
            if (f[1]){ // user was correct
                show_form('correct_sol_disp_'.concat(f[0]));
            } else {
                show_form('incorrect_sol_disp_'.concat(f[0]));
            }
        }
        if (alldone){
            show_form('alldone_feedback');
        }
    }
    /* previous if nest
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
     */
}

function test_func(){
    alert("inside test_func");
}

// generic hide and show functions
function hide_form(id){
    if (document.getElementById(id)){
        document.getElementById(String(id)).hidden = true;
    } else{
        alert('id not found');
    }
}
function show_form(id){
    if (document.getElementById(id)){
        document.getElementById(id).hidden = false;
    } else{
        alert('id not found');
    }
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

