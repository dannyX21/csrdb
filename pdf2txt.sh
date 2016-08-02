#!/bin/bash

eval $(lesspipe)
less $1 > $2 2>&1
