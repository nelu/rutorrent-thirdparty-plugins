#!/bin/bash

cmd=$(echo $*);
bash -c "$cmd" 2>&1;

echo "{\"exitcode\":$?}";
exit 0;