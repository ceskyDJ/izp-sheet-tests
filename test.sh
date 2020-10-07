#!/bin/bash
# This file is for local tests (on GNU/Linux OSes) only

# Compile C lang source codes
gcc -std=c99 -Wall -Wextra -Werror sheet.c -o tmp/sheet

# Run PHP tests
php test/test.php