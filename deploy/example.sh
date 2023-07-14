#!/bin/sh

#Include the following function in your .bash_profile file. Make sure to remove the #s in from of the function.
# qdeploy(){
#     bash "./deploy/$1.sh";
# }

#Copy this file and name it the name of the branch (e.g. develop.sh).

#Run this file in the terminal by typing `qdeploy INSERT_BRANCH_NAME`

#Update the strings below that start with "INSERT_*".
curl -X POST INSERT_BRANCH_DEPLOY_WEBHOOK
