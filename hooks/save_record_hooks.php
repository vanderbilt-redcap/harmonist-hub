<?php
include_once __DIR__ ."/../functions.php";

#Depending on the project que add one hook or another
if($project_id == IEDEA_SOP){
    include_once("save_record_SOP.php");
}else if($project_id == IEDEA_RMANAGER){
    include_once("save_record_requestManager.php");
}else if($project_id == IEDEA_COMMENTSVOTES){
    include_once("save_record_commentsAndVotes.php");
}else if($project_id == IEDEA_SOPCOMMENTS){
    include_once("save_record_SOP_comments.php");
}