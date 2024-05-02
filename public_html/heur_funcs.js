function disp_sections(input, steps, done, feedback, alldone, k_form, k_disp, k_correct){
    hide_input_disp();
    show_input_disp();
    hide_form('k_forms');
    hide_form('k_disp');
    hide_form('k_feedback_correct');
    hide_form('k_feedback_incorrect');
    for(let step of steps){
        hide_form('steps_disp_'.concat(step[0]));
        hide_form('attr_disp_'.concat(step[0]));
        hide_form('steps_form_'.concat(step[0]));
    }
    for(let d of done){
        hide_form('incorrect_sol_disp_'.concat(d[0]));
        hide_form('correct_sol_disp_'.concat(d[0]));
    }
    hide_form('alldone_feedback');
    if(k_form){
        show_form('k_forms');
    } else{
        hide_form('k_forms');
    }
    
    if (input){
        show_input_disp();
        if (k_disp){
            show_form('k_disp');
            if (k_correct){
                // show all steps forms
                
                show_form('k_feedback_correct');
                hide_form('k_forms');
                for(let step of steps){
                    if(step[1]){ // there are steps for the attribute
                        show_form('steps_disp_'.concat(step[0]));
                    } else { // there are no steps for the attribute
                        hide_form('steps_disp_'.concat(step[0]));
                    }
                    show_form('attr_disp_'.concat(step[0]));
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
            } else {
                show_form('k_feedback_incorrect');
                
            }
        }
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
        alert('hide--id not found');
        alert(id);
    }
}
function show_form(id){
    if (document.getElementById(id)){
        document.getElementById(id).hidden = false;
    } else{
        alert('show--id not found');
        alert(id);
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