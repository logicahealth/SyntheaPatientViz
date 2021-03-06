Synthea Visualization
=====================
This is a quick D3 visualization which can be used to display Synthea data
in a longitudinal format. It helps with modeling Synthea data and with
selecting patients for import selectively.

How to use this tool with Synthea
+++++++++++++++++++++++++++++++++
This page can used by cloning the source code and using it with Apache or the
PHP command line interpreter; or you can use the REST interface against the
OSEHRA hosted version to convert the files into local HTML files that you can
view locally.

REST Interface against OSEHRA Server
------------------------------------
This page can be accessed with the command line program (like curl) to pull
down an HTML page with the necessary functions and information.

For example:

.. code-block:: bash

  curl https://code.osehra.org/synthea/synthea_upload.php -d @/d/wamp/www/synthea/syntheaold/Arianna984\ Hand679_130ec445-9989-49a4-888c-233bcfa9e7fc.json > arianna.html

will pull down the visualization for that single patient.

For reference, here are a set of commands (for \*nix and for Windows) that will
create HTML pages for each patient output from Synthea.

\*Nix:

.. code-block:: bash

  folder="/Users/sam/workspace/repo/synthea/output/fhir" # Replace this with your correct path
  rm $folder/*.html
  for f in $folder/*_*.json; do echo $f; curl https://code.osehra.org/synthea/synthea_upload.php -d @$f > $f.html; done

Windows:

.. code-block:: powershell

  $folder="C:\Users\Hp\synthea\output\fhir" # Replace this with your correct path
  del $folder/*.html
  $files = Get-ChildItem $folder -Filter *_*.json
  foreach ($f in $files) {
    echo $f.FullName
    Invoke-RestMethod -Uri https://code.osehra.org/synthea/synthea_upload.php -Method Post -InFile $f.FullName > ($f.FullName + ".html")
  }


Serve Locally using this Repository
-----------------------------------

For those with a directory of files from Synthea who wish to view the data can
also clone the repository and place the files in a directory called ``local``.

A directory structure like this::

  joe.snyder@thessia MINGW64 /d/wamp/www/synthea (master)
  $ ls -R
  .:
  LICENSE  local  README.rst  synthea_scale.css  synthea_upload.php

  ./local:
  'Arianna984 Hand679_130ec445-9989-49a4-888c-233bcfa9e7fc.json'
  'Donovan745 Klein929_d5e1fc95-147c-4e3b-9f07-616d203ad53d.json'
  'Elmira442 Ziemann98_52a7214f-1afa-431f-8808-66ed12a232d1.json'
  'Elmo857 Lynch190_0563d2b3-4360-40be-bcf4-037202ae3212.json'
  'Irving123 Lemke654_3969c969-a2a5-43da-bc98-4326875266cc.json'
  'Marquerite715 Price929_faa43884-e69f-4e1c-8d02-ee99158473a0.json'
  'Monica985 Aleman808_f59723b6-881c-4460-bb46-53f65b79237d.json'
  'Sabina296 Bergnaum523_618fcbb0-ae23-40a4-bd93-e64420b49936.json'
  Susana.json
  'Thea616 Mayert710_bcec58d0-c5df-4493-9038-4917bbe0c478.json'
  'Valentine262 Shields502_74053ae0-2610-42de-b91c-5feb5c525829.json'

will populate the "Select synthetic patient file" box and allow the user to
view the patient information.

Here are step by step instructions using PHP command line server.

.. code-block:: bash

  git clone https://github.com/OSEHRA-Sandbox/SyntheaPatientViz
  cd SyntheaPatientViz
  mkdir local
  cp ../../synthea/output/fhir/*_*.json local/
  php -S localhost:8000
  # Use your browser to navigate to localhost:8000/synthea_upload.php

How to use the Visualization
+++++++++++++++++++++++++++++

The visualization can be interacted with in a variety of ways:

Viewing patient information
----------------------------

Viewing the information given in the patient display can be accessed in one of
two ways: by clicking on the bar and hovering over it

Hovering over the bar will show a small snippet of information about the entry

* type
* status
* description
* date

Clicking on the bar will bring up modal window with the total set of
information about the entry.

Snapshot of information
%%%%%%%%%%%%%%%%%%%%%%%

To view a look at the patient's most recent object in each category,
'right click' on the timeline to place a new bar.  Clicking on that bar
will display a modal window which shows the last object which was displayed for
each category before the bar's position in the patient's history.

Changing the displayed time range
----------------------------------

The default time range of the page is from the date of the first piece of
information to the end of the current year. To change the date range that is
displayed: enter new dates into the boxes following the
"Select date range to view" text.  The start date should be in the left-most
box.

Filtering shown values
-----------------------

By clicking on the text types in the ``Color Legend``, the visualization will
filter existing objects to only display those that are selected.  The selected
object types are found in their display color while non-selected ones are grey.

Multiple entries may be selected to display at the same time.  If you de-select
all choices, the page will revert back to displaying all types.

Panning and zooming
---------------------

When multiple entries within a report type happen on the same day, the bar for
each entry is shortened to allow all entries to be show on the day.

Double-clicking the mouse will zoom in the page allowing for easier access to
the smallest bars of the display.  Double-clicking again will zoom out allowing
for display of the overall picture again.

**Note: The assets in this repository were stewarded by OSEHRA and transferred to Logica on February 14th, 2020 upon OSEHRA's permanent closure. While the work remains available as Open Source for continued community use, copyright and licensing statements may not have been updated to reflect this transfer.**
