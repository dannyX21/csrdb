Customer Service Automated Order Entry
==

Web application to allow automated order entry into the MRP System.
This application does the following tasks:
* Converts a PDF file (Purchase Order file) to a TXT file.
* Extracts information from the Purchase Order file using regular expressions.
* Populates the data base with the information extracted from the Purchase Order files.
* Verifies revision level for each line.
* Verifies unit price for each line, avoiding all the manual work and possible human errors.
* Once the information is confirmed, the order is entered into the MRP Automatically using pyautogui.

