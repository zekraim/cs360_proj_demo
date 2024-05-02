function disp_sections(input, tree_disp, tree_form, candidates_form, candidates_disp, feedback_correct, feedback_incorrect){
    hide_form('input_form');
    hide_form('steps_disp');
    hide_form('steps_form');
    hide_form('done_button');
    hide_form('list_candidates_form');
    hide_form('candidates_disp');
    hide_form('correct_feedback');
    hide_form('incorrect_feedback');
    if(input){
        show_form('input_form');
    }
    if(tree_disp){
        show_form('steps_disp');
    }
    if(tree_form){
        show_form('steps_form');
        show_form('done_button');
    }
    if(candidates_form){
        show_form('list_candidates_form');
    }
    if(candidates_disp){
        show_form('candidates_disp');
    }
    if(feedback_correct){
        show_form('correct_feedback');
    }
    if(feedback_incorrect){
        show_form('incorrect_feedback');
    }
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



