Sample Analytics Cleanup Code


Incentient’s Smartcellar product (iPad Wine/Beverage Menu) has an Analytics Application that tracks user’s selections.  The Smartcellar front end url’s are saved in the database, parsed, and saved in tracking tables that are used by the Analytics application to generate the data for the charts.  The Analytics application uses charts.js to display charts on the front end based on user entered input parameters (date range, beverage type, etc).  
The Analytics Application was developed by two other developers, who were fired shortly after I started at Incentient.  I was given the task of fixing bugs, which led me to clarifying the content of some of the charts (you can’t fix something if you don’t know how it is supposed to work), and for some of the charts, rewriting to implement new requirements or improve performance.
I've included in this repository 2 files, the original code (ChartviewsController_ORIG.php) and the improved code (ChartsviewController_NEW.php) for your review.


1.  Added new dbUtilities class that handles all database calls, this was part of upgrade to support php versions >= 5.5.
Added a layer of abstraction to database, instead of just replacing all mysql* calls with mysqli*.

2.  Cleary defined requirements for some of the charts.
Many of the charts were not displaying correct data.  Actually, it wasn’t
     clear what many of the charts were actually supposed to be displaying.
     Not sure how you can write code when you aren’t really sure what the requirements are, but over the years I’ve seen many developers do this, instead of finding out what the requirements really are.

3.  Fixed many bugs.  Some of the charts were not selecting the data correctly.

4.  Improved performance on many reports.  For example - the original version of navigationAction had some bugs, and took almost a minute to process.  By breaking down the sql query  logic to a few simple queries, and using php associative arrays to store calculations and sort, the processing time was reduced to less than 10 seconds.

5.  Improved overall maintainability of the code by adding appropriate comments, using functions for repeated logic that I added, instead of repeating the same 4 lines of code all over the place, and adding revision history.  Especially since this was a clean-up effort, revision history is important to make sure if a new bug is introduced, it can be quickly tracked down to the change/rewrite that might have caused it.

6.  Improved maintainability by updating the functions that are used to generate pdf and excel exports to use the data already generated for the chart (you run the exports when the front end displays the charts).  Many of the charts had the same  code/logic repeated in the pdf/excel export code.
 I was very disappointed when QA told me that the fixes weren't in all of the exports, and could not actually believe that a developer would copy this logic/code in 2 places :(
7.  This project was built using Zend framework.  I’m not sure why the decision was made to put all of the business logic in the controllers instead of the models.  My job was to fix bugs and improve performance.  Based on the schedule, I fixed bugs and rewrote/refactored what was required.  A rewrite was not an option.



