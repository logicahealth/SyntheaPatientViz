<!DOCTYPE html>
<html>
  <title>Synthea Patient Display</title>
  <head>
  <meta http-equiv="Content-Type" content="text/html;charset=utf-8"/>
  <!-- Latest compiled and minified JavaScript -->
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.2.0/js/bootstrap.min.js"></script>
  <script src="https://d3js.org/d3.v3.min.js"></script>
  <script src="https://d3js.org/d3-queue.v3.min.js"></script>
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap.min.css"/>
  <script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.11.0/jquery-ui.min.js"></script>
  <link rel="stylesheet" href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.11.0/themes/smoothness/jquery-ui.css"/>
  <link rel="stylesheet" href="https://code.jquery.com/ui/1.11.0/themes/smoothness/jquery-ui.css"/>
    <?php
      include_once "synthea_scale.css";
    ?>
    <!-- JQuery Buttons -->
    <script type="text/javascript">
      $(function() {
        $( "button" ).button().click(function(event){
          event.preventDefault();
        });
        fileName = window.location.href.substring(window.location.href.lastIndexOf('/')+1)
        $('a[href="'+fileName+'"]').parents("#navigation_buttons li").each(function (i) {
            $(this).removeClass().addClass("active");
        });
      });

    </script>
  </head>

<body>
  <div>
    <img src="http://www.osehra.org/profiles/drupal_commons/themes/commons_osehra_earth/logo.png" style="border-width:10" height="48" width="200" alt="OSEHRA Logo">
    <h2>OSEHRA Synthea Viewer</h2>
    <!-- <select id="category"></select> -->
  </div>
  <!-- Tooltip -->
     <div id="toolTip" class="tooltip" style="opacity:0;">
      <div id="header1" class="header"></div>
      <div id="installDate" ></div>
      <div id="filesTip" ></div>
      <div id="routinesTip" ></div>
      <div class="tooltipTail"></div>
    </div>
    <div id="ctrlToolTip" class="tooltip" style="opacity:0;">
      <div id="header1" class="header"></div>
    </div>
  </div>
  <div id="descrHeader" ></div>
   <div>

    <label > Select synthetic patient file locally:</label>
    <select id="vivSelect"></select>
    <div id='verticalLine' style='border-left: thick solid #ff0000;'></div>
    <label > or enter a FHIR server to query:</label>
    <div>
      <label for='serverURL' > URL of FHIR server:</label>
      <input type='text' id='serverURL' value='http://localhost:8080/api/'></input>
      <label for='patID' > ID value of patient:</label>
      <input type='text' id='patID' value=''></input>
      <input type='button' id="fhirRequest" value='Query'></input>
    </div>
  <script type="application/javascript">

    var files = "<?php  foreach(glob('./local/*.json') as $filename) { echo $filename.",";  };?>"
    filesArray = files.split(",")
    filesArray.pop()
    $("#vivSelect").append("<option selected disabled>Select Value...</option>");
    for( var localFile in filesArray) {
      $("#vivSelect").append("<option val='"+filesArray[localFile]+"'>"+filesArray[localFile]+"</option>");
    }
  </script>
  </br>
    <label title="Set the data parameters for the timeline.">Select date range to view:</label>
    <!--input type="text" id="timeline_date_start" >
    <input type="text" id="timeline_date_stop">
    <button id="timeline_date_update">Update</button -->
    <div id="timeCtl"></div>
    <button id="timeline_date_reset">Reset</button>
    <img id="loadWheel" title="Loading" src="./images/loading-big.gif" style="display: none;"/>
    <div id="patInfoPlaceholder"/>
  </div>



<div id="dialog-modal" style="display:none;">
        <div id='filteredObjs'></div>
    </div>
 </div>
  <div id="treeview_placeholder">
    <div id="legend_placeholder" style="position:absolute; left:20px;"></div>
    <svg id='timeline' class="chart" style="position:absolute; left:100px"></svg>
  </div>

<script type="application/javascript">
var margin = {top: 40, right: 40, bottom: 40, left:0},
        width = 1500,
        height =750;
var originalTransform = [60,60];

var borderColor = {
  "MedicationDispense": "red",
  "MedicationStatement": "black",
  "MedicationAdministration": "blue",
  "Appointment": "black",
  "Encounter": "blue"
}

var SynVisitDict = { "DiagnosticReport": {"regex": /DiagnosticReport/, "color": "red", "height":0,"sd": "effectiveDateTime" ,"ed":"","desc":{"code": "display|text"}, "status":"status"},
  "Claim": {"regex": /Claim/, "color": "orange", "height":.1,"sd": "billablePeriod" ,"ed":"billablePeriod","desc":{"total":"value&currency"}, "status":"status"},
  "MedicationRequest": {"regex": /Medication.+/, "color": "yellow", "height":.2,"sd": ["authoredOn", "effectivePeriod"] ,"ed":"","desc":{"medicationCodeableConcept":"display|text","medicationReference":"display"}, "status":"status"},
  "Encounter": {"regex": /Encounter|Appointment/, "color": "green", "height":.3,"sd": ["period","start"] ,"ed":"period","desc":{"valueString":"display|text","type":"display|text"}, "status":"status"},
  "Observation": {"regex": /Observation/, "color": "blue", "height":.4,"sd": ["effectiveDateTime","issued"] ,"ed":"","desc":"code" , "status":"status"},
  "CarePlan": {"regex": /CarePlan/, "color": "purple", "height":.5,"sd": "period" ,"ed":"period","desc": {"category": 'display|text'}, "status":"status"},
  "Procedure": {"regex": /Procedure/, "color": "steelblue", "height":.6,"sd": ["performedDateTime", "performedPeriod"] ,"ed":["performedDateTime", "performedPeriod"],"desc":{"code" : "display|text"}, "status":"status"},
  "Condition": {"regex": /Condition/, "color": "black", "height":.7,"sd": "onsetDateTime" ,"ed":"abatementDateTime","desc":{"code" : "display|text"}, "status":"clinicalStatus"},
  "Immunization": {"regex": /Immunization/, "color": "pink", "height":.8,"sd": ["date","occurrenceDateTime"] ,"ed":"","desc":{"vaccineCode": "display|text"}, "status":"status"},
}

var patDict = ["name","gender","birthDate", "deceasedDateTime","address"]
var chartHeight = height - margin.top - margin.bottom;
var chartWidth = width - margin.left - margin.right
var y = d3.scale.linear()
    .range([chartHeight, 0]);
var x = d3.time.scale()
  .range([0, width]);
var shownPackage;
var patInfo = {};
var index = 0
colors = d3.scale.category20b()
backgroundColors = d3.scale.category20()
var currentDate = new Date();
var currentJSON;
var start = "";
var stop = "";
var existingConds = [];
var longCondition = {};
var medicationIds = {};
var conditionList = [];
var condCounter = 0;
var dateArray = [];
var condDepthMax = 1
var endDate = currentDate.getFullYear() + "-12-31"
var organizationDict = {};
var organizationColorDict = {};
var legendShapeChart = d3.select('#legend_placeholder').insert("svg")
                .attr("height", 1000)
                .attr("width",75)
//Taken from http://bl.ocks.org/robschmuecker/6afc2ecb05b191359862
// =================================================================
var panSpeed = 200;

var isZoomed=false;
function zoomFunc() {
    var svg = d3.select('#treeview_placeholder #timeline').select('g')
    var updatedTransform  = svg.attr("transform")
    translateText= "translate(" + d3.event.translate + ")";
    updatedTransform = updatedTransform.replace(/translate\([0-9., -]+\)/,translateText);
    svg.attr("transform", updatedTransform);
}

var zoomListener = d3.behavior.zoom().scaleExtent([.5,2])
                                     .on("zoom", zoomFunc);
//===================================================================
/*
*  Function to handle the updating of the time scale.
*  takes the values of the two date boxes and uses that to
*  redraw the graph, keeping the same package, with the values
*/
$("#timeline_date_update").click( function() {
  if( $("#timeline_date_start")[0].value == $("#timeline_date_stop")[0].value) {
    alert("Cannot show data that begins and ends on the same day")
  }
  else {
    resetMenuFile(currentJSON, $("#timeline_date_start")[0].value, $("#timeline_date_stop")[0].value,Object.keys(SynVisitDict))
  }
})


/*
*   Funtion to catch the FHIR query capability and send the ajax call
*/

$('#fhirRequest').click(function() {

  id = $('#patID').val()
  url = $('#serverURL').val()
  query= (url+'Patient/' + id + '/$everything')
  $("#loadWheel").show(0)
  $.ajax({
    url:query,
    success: function(result){
      currentJSON=result
      resetMenuFile(currentJSON.entry,"","", Object.keys(SynVisitDict))
      createLegend();
    },
    error: function(result) {
      var error = result.responseText
      if (error === "") {
          error = "No response from server.  Ensure FHIR server is running"
      } else {
          error = JSON.parse(result.responseText).issue[0].diagnostics
      }
      alert(`Error: ${error}`)
      $("#loadWheel").hide(0)
    },
  })
})
/*
*  Function to handle the resetting of the time scale.
*  Clears the values of the two date boxes and redraws the
*  graph, keeping the same package, and uses the default values
*  for date selection
*/

$("#timeline_date_reset").click( function() {
  if (currentJSON !== undefined) {
      $("#loadWheel").show(0)
      // HACK: Load wheel image doesn't have time to "show" before resetting occurs
      // setTimeout delays the call to resetMenuFile until shown
      window.setTimeout( function () {
          d3.select('#legend_placeholder').selectAll("svg").selectAll("*").attr("class","")
          resetMenuFile(currentJSON.entry,"","",Object.keys(SynVisitDict))
          createLegend();
      },100);
  }
})

/*
*  Function which is called when each box in the chart has the mouse
*  hovering over it.  It generates the tooltip and positions it at
*  the location of the mouse event.
*/
function rect_onMouseOver(d) {
  var header1Text = "Type: " + d.resourceType +
                    "</br>Status: " +  findStatus(d) +
                    "</br>Description: " + findDescription(d) +
                    "</br>Date: " + findStartDate(d);
  $('#header1').html(header1Text);
  d3.select("#toolTip").style("left", (d3.event.pageX + 0) + "px")
          .style("top", (d3.event.pageY - 0) + "px")
          .style("opacity", ".9");
}

/*
*   Clears the tooltip information and hides the tooltip from view
*/
function rect_onMouseOut(d) {
  $('#header1').text("");
  $('#installDate').text("");
  $('#routinesTip').text("");
  $('#filesTip').text("");
  d3.select("#toolTip").style("opacity", "0");
}

/*
* When each bar is clicked on, show the files page for each install
*/

function rect_onClick(d) {
    var overlayDialogObj = {
          autoOpen: true,
          height: ($(window).height() - 200),
          position : {my: "top center", at: "top center", of: $("#timeline")},
          width: 700,
          modal: true,
          title: "Filtered Object Information",
          open: function (event) {
           $("#filteredObjs").empty();
            Object.keys(d).forEach(function(key) {
                var objectData = d[key]
                /*if(key === "serviceProvider") {
                    objectData = organizationDict[d.serviceProvider.reference.substr(9)]
                }*/
                $("#filteredObjs").append("<p>"+key+":<pre>"+JSON.stringify(objectData,null,2)+"</pre></p>")
            });
          }
       };
       $('#dialog-modal').dialog(overlayDialogObj);
       $('#dialog-modal').show();
}

function summary_onClick(d) {
    var overlayDialogObj = {
          autoOpen: true,
          height: ($(window).height() - 200),
          position : {my: "top center", at: "top center", of: $("#timeline")},
          width: 700,
          modal: true,
          title: "Summary of patient information",
          open: function (event) {
           $("#filteredObjs").empty();
            Object.keys(d).forEach(function(key) {
                var objectData = d[key]
                $("#filteredObjs").append("<p><b>"+key+":</b>"+objectData+"</p>")
            });
          }
       };
       $('#dialog-modal').dialog(overlayDialogObj);
       $('#dialog-modal').show();
}

function findStatus(d) {
    var status = "No Information"
    if (Object.keys(d).indexOf(SynVisitDict[d.overallType].status) !== -1) {
      status = d[SynVisitDict[d.overallType].status]
    }
    if (Object.keys(status).indexOf('coding') !== -1 ) {
       status = d[SynVisitDict[d.overallType].status]['coding'][0]['code']
    }
    return status
}

function findStartDate(d) {
  var startDate=''
  if( Object.keys(SynVisitDict).indexOf(d.overallType) !== -1) {
    var dateKeys = [SynVisitDict[d.overallType]["sd"]]
    if (typeof SynVisitDict[d.overallType]["sd"] == 'object') {
      dateKeys = SynVisitDict[d.overallType]["sd"];
    }
    dateKeys.some(function(dateKey) {
      if (Object.keys(d).indexOf(dateKey) != -1) {
        startDate = d[dateKey]
        if (typeof d[dateKey] == 'object') {
           startDate = d[dateKey]['start'];
        }
      }
      return startDate !== ''
    })
    if (d.overallType == "MedicationRequest") {
      startDate = medicationIds[d.id]
    }
  }
  return startDate
}
function findStopDate(d) {
  var stopDate = endDate
  if(SynVisitDict[d.overallType]["ed"] == "") {
   return 3;
  }
  var dateKeys = [SynVisitDict[d.overallType]["ed"]]
  if (typeof SynVisitDict[d.overallType]["ed"] == 'object') {
    dateKeys = SynVisitDict[d.overallType]["ed"];
  }
  dateKeys.forEach(function(dateKey) {
  if (Object.keys(d).indexOf(dateKey) != -1) {
      stopDate = d[dateKey]
      if (typeof d[dateKey] == 'object') {
         stopDate = d[dateKey]['start'];
      }
    }
  })
  return stopDate
}
function findObsDescription(d) {
  var descObj = d[SynVisitDict[d.overallType]["desc"]];
  var text = ""
  var value = ""
  var objsToCheck = [d]
  if (Object.keys(d).indexOf("component") != -1) {
      objsToCheck=d['component'];
  }
  objsToCheck.forEach( function(checkObj) {
      // check for text description
      text = checkObj["text"]
      if (Object.keys(descObj).indexOf('coding') !== -1) {
          text = descObj["coding"][0]["display"]
      }
      //Check for value
      if (Object.keys(checkObj).indexOf("valueQuantity") != -1) {
        value =  `${checkObj["valueQuantity"]["value"]} ${checkObj["valueQuantity"]["unit"]}`
      } else if (Object.keys(checkObj).indexOf("valueString") != -1) {
        if (typeof checkObj["valueString"] == 'object') {
            value = checkObj["valueString"]["value"]
        } else {
          value = checkObj["valueString"]
        }
      } else if (Object.keys(checkObj).indexOf("valueCodeableConcept") != -1) {
        text = checkObj["valueCodeableConcept"]["text"]
      }
  })
  return `${text} ( ${value} )`
}
function findDescription(d) {
    // Special logic for observations
    if (d.overallType == "Observation" ) {
          return findObsDescription(d)
    }
    // Start with a default value for all objects
    var descriptionVals = {'coding':"display"}
    var description = ""
    // Put all value pairs into description options
    if (typeof SynVisitDict[d.overallType]["desc"] == 'object') {
      Object.keys(SynVisitDict[d.overallType]["desc"]).forEach( function(entry) {
        descriptionVals[entry] = SynVisitDict[d.overallType]["desc"][entry]
      })
    } else {
      descriptionVals[SynVisitDict[d.overallType]["desc"]] = ""
    }
    // Loop through each pair to see if a description is found there
    // Use a "some" function to prevent extra loops from happening when
    // a description is found
    Object.keys(descriptionVals).some(function(tag) {
      //Check resultant string for values
      // '&' indicates the two should be concat-ed to make the description
      // '|' indicates the value could be in either of the objects
      if (Object.keys(d).indexOf(tag) !== -1) {
        var dataObject = d[tag]
        // Loop your way "down" through the objects until coding or arrays are not found.
        while(true) {
          var codingFlag = true
          var arrayFlag = true
          // Check for "coding" object, removes a layer
          if (Object.keys(dataObject).indexOf('coding') !== -1) {
            codingFlag = false;
            dataObject = dataObject["coding"]
          }
          // Assume the data we want is in the first object of the array
          if (Array.isArray(dataObject)) {
            arrayFlag = false;
            dataObject = dataObject[0]
          }
          //No more layers, break and check for data
          if (arrayFlag && codingFlag) {
              break
          }
        }
        descriptionVals[tag].split('|').forEach(function(test) {
          test.split('&').forEach(function(val) {
            if (dataObject[val] !== undefined) {
              description += dataObject[val]
            }
          })
          if (description !== "") {
            return true
          }
        })

      }
    })
    return description
}
  d3.select("#vivSelect").on("change", function(){
    $("#loadWheel").show(0)
    d3.json(d3.select('#vivSelect').property('value'), function(json) {
        currentJSON=json;
        resetMenuFile(json.entry,"","", Object.keys(SynVisitDict))
        createLegend();
    });
  });

/*
*  Main function to set up the scales and objects necessary to show
*  the install information
*/
function resetMenuFile(json,startVal,stopVal,filterList) {
  condDepthMax = 0
  condCounter = 0
  endDate = currentDate.getFullYear() + "-12-31T00:00:00"
  existingConds = [];
  longCondition = {};
  conditionList = [];
  medicationIds = {};

  //Read in the INSTALL JSON file
    /*
    *  Capture the package specific information.  The start date
    *  of the scale should be the install date of the first patch
    *  The end date is set to be some time in the future.
    *  TODO: Add a more specific date to the end.
    */
    var ptInfoArray = [];
    dateArray = [];
    json.forEach(function (val, indx) {
      Object.keys(SynVisitDict).forEach(function(key) {
        if(SynVisitDict[key]['regex'].exec(val["resource"].resourceType)) {
          dateArray.push(findStartDate(val['resource']))
          val["resource"]['overallType'] = key
          if (filterList.indexOf(val["resource"]['overallType']) != -1) {
            ptInfoArray.push(val["resource"])
          }
          if (val["resource"]['resourceType'] == "Condition"){
                conditionList.push(val['resource'])
          } else if (val["resource"]['resourceType'] == "Organization") {
             if (Object.keys(organizationDict).indexOf(val["resource"]['id']) == -1) {
                 organizationDict[val["resource"]['id']] = val["resource"]
                 organizationColorDict[val["resource"]['identifier'][0]["value"]] = colors(indx)
             }
          } else if (val["resource"]['overallType'] == "MedicationRequest") {
              var medStartDate = ""
              if (Object.keys(val['resource']).indexOf('effectivePeriod') != -1) {
                  medStartDate = val['resource']["effectivePeriod"]['start'];
              } else {
                medStartDate = val['resource']["authoredOn"]
              }
              if (medStartDate !== undefined) {
                  medicationIds[val['resource']['id']] = medStartDate
              }
          }
        }else if (val["resource"]['resourceType'] == "Patient") {
              patInfo = val["resource"];
          }
      });
    })
    ptInfoArray.sort(function(a,b) {return findStartDate(a) > findStartDate(b)?1:-1})
    conditionList.sort(function(a,b) {return findStartDate(a) > findStartDate(b)?1:-1})
    conditionList.forEach(function(val) {
      // Increase count of conditions as each condition is read in
      // Here to observe conditions with no abatement time.
      ++condCounter
      if (Object.keys(longCondition).indexOf(findStartDate(val)) == -1) {
        // If is new date, add its value here assuming new increase is correct
        longCondition[findStartDate(val)] = condCounter
      } else {
        // Otherwise, increment known number
        longCondition[findStartDate(val)]++
      }
      if (Object.keys(longCondition).indexOf(findStopDate(val)) == -1) {
        //Find conditions end date, and add decrement on that date
        longCondition[findStopDate(val)] = longCondition[findStartDate(val)] - 1
        // if not an "infinite" condition, drop the overall number of conditions.
        if (findStopDate(val) !== endDate) {
          condCounter--
        }
      }
    })
    createPatientLegend(patInfo);
    //ptInfoArray.sort(function(a,b) {console.log(a); return a.issued.localeCompare(b.issued); });
    start = startVal
    stop = stopVal
    if (start === "") {start = findStartDate(ptInfoArray[0])}
    if (stop === "") {
      stop = endDate
      if (Object.keys(patInfo).indexOf("deceasedDateTime") !== -1) {
        endDate = patInfo["deceasedDateTime"];
        stop = endDate.substr(0,4) + "-12-31"
      }
    }
    var svg = d3.select('#treeview_placeholder').select('#timeline')
    svg.selectAll("*").remove();

    // Generate a time scale to position the dates along the axis
    // domain uses the dates above, rangeRound is set to keep it within
    // the visualization window
    x = d3.time.scale()
      .domain([new Date(start), new Date(stop)])
      .range([0, width]);

    // Generate the xAxis for the above scale
    var xAxis = d3.svg.axis()
      .scale(x)
      .orient('top')
      .tickSize(10)
      .tickPadding(8);
    ptInfoArray.sort(function(a,b) {
              return (x(new Date(findStopDate(b))) - x(new Date(findStartDate(b)))) - (x(new Date(findStopDate(a))) - x(new Date(findStartDate(a))));
    });

    // Add the chart to the SVG object
    svg.attr('class', 'chart')
      .attr('width', width)
      .attr('height', height)
      .call(zoomListener)
      .on("wheel.zoom", null)
      .on("dblclick.zoom", function(event) {
         var updatedTransform  = svg.select('g').attr("transform")
         if (updatedTransform.lastIndexOf('scale') == -1){
             updatedTransform= updatedTransform+"scale(1)";
         }
         var scaleTxt = "scale(1.4)"
         if (isZoomed) {
             scaleTxt = "scale(1)";
         }
         isZoomed = !isZoomed;
         updatedTransform= updatedTransform.replace(/scale\([0-9.]+\)/,scaleTxt);
         svg.select('g').attr("transform",updatedTransform);
      })
        .on("contextmenu", function (d, i) {
            d3.event.preventDefault();
            d3.selectAll(".summaryBar").remove()
            var display = svg.select('g')
            var transform = display.attr("transform")
            var regex = /translate\(([-0-9]+)[,] *([-0-9]+)\)(?:scale\(([-0-9.]+)\))*/
            var matches = regex.exec(transform);
            var scale= 1
            if (matches[3]) {scale = matches[3]}
            var xVal =(d3.event.x-matches[1]-100)/scale
            var selectBar = display.append("line")
                                     .attr('class','summaryBar')
                                     .attr("x1", xVal)
                                     .attr("y1", 0)
                                     .attr("x2", xVal)
                                     .attr("y2", height)
                                     .attr("stroke-width", 5)
                                     .on("click", function(event) {
                                        findLastObjects(xVal)
                                     })

           // react on right-clicking
        });
    svg.append('g')
      .attr('transform', 'translate(' + margin.left + ', ' + margin.top + ')')
      .attr('class', 'x axis')
      .attr('width',chartWidth)
      //Adds xAXIS to g

    zoomListener.translate(originalTransform).scale(1)
    var svgG = d3.select('#treeview_placeholder').select('#timeline').select('g')
    svgG.selectAll('.rect:not(.legend)')
      .data(ptInfoArray)
      .enter().append('rect')
      .attr('class', 'bar')
      .attr('x', function(d) {return x(new Date(findStartDate(d))); })
      .attr('width', function(d) {
          var yCoord = height * SynVisitDict[d.overallType]["height"]
          if (d.overallType === "Condition"){
              var stopDate = x(new Date(findStopDate(d)))
              var startDate = x(new Date(findStartDate(d)))
              d.depthVal=0
              existingConds.forEach(function(val, i) {
                  if ((val[0] <= startDate && startDate <= val[1]) || (val[0] <= stopDate && stopDate <= val[1])) {
                      if (d.depthVal >= val[2]) { d.depthVal++; }

                      if (d.depthVal > condDepthMax) { condDepthMax = d.depthVal;}
                  }
              })
              existingConds.push([startDate,stopDate,d.depthVal])
              return stopDate - startDate;
          }
          else {return 3}
      })
      .attr('y', function(d) {
          if (d.overallType === "Condition"){
              var heightVal = (height * .095)/(condDepthMax+1)
              return (height * SynVisitDict[d.overallType]["height"]) + (heightVal * d.depthVal);
          } else {
            var yCoord = height * SynVisitDict[d.overallType]["height"]
            d.originalY = yCoord
            return yCoord
          }
      })
      .attr('originalY', function(d) { return d.originalY})

      .attr('height', function(d) {
          // Check to see if existing objects need to be shrunk
          var xCoord = x(new Date(findStartDate(d)))
          var yCoord = height * SynVisitDict[d.overallType]["height"]
          var existingBars;
          if (d.overallType === "Condition"){
              var heightVal = (height * .095)/(condDepthMax+1)
          } else {
              existingBars= $(".bar[x='"+xCoord+"'][originalY='"+yCoord+"']")
              var heightVal = (height * .095)/(existingBars.length)
              existingBars.each(function (index,obj) {
                $(obj).attr("height", heightVal);
                $(obj).attr("y", yCoord + (heightVal * index-1));
              })
          }
          return heightVal
      })
      .attr("fill", function(d,i) {return SynVisitDict[d.overallType]["color"]; })
      .attr('stroke', function(d,i) {
          var color = "white";
          /*if (d.serviceProvider) {
            color = organizationColorDict[organizationDict[d.serviceProvider.reference.substr(9)]['identifier'][0]["value"]]
          }*/
          if (Object.keys(borderColor).indexOf(d.resourceType) != -1) {
            color = borderColor[d.resourceType]
          }
          return color;
      })
      .attr('stroke-linecap', 'butt')
      .attr('stroke-width', 1.5)

      svgG.call(xAxis)
      // Selecting the text to to be able to modify the labels for the axis
      // 1. have the text run vertically
      // 2. have the anchor be at the start of the word, not the middle.
      .selectAll("text")
      .attr("y", 0)
      .attr("x", -11)
      .attr("dy", ".35em")
      .attr("transform", "rotate(90)")
      .style("text-anchor", "end");
    /*
    *  Set all of the ".bar" classed bars, all of the install information, to have the
    *  mouse events described above.
    */

    svg.selectAll('.bar')
      .on("mouseover", rect_onMouseOver)
      .on("mouseout", rect_onMouseOut)
      .on("click", rect_onClick);
    $("#loadWheel").hide()
};

function findLastObjects(xPos) {
    var lastObjects = {}
    var lastObjectsPretty = {}
    d3.selectAll("rect.bar")[0].forEach(function(obj,i) {
      var entry = d3.select(obj)[0][0];
      var endX = chartWidth
      entry.endX = endX
      if (Object.keys(entry.__data__).indexOf('abatementDateTime') != -1) {
        endX = x(new Date(entry.__data__['abatementDateTime']));
        entry.endX = endX
      }
      if (Object.keys(lastObjects).indexOf(entry.__data__.resourceType) == -1) {
        lastObjects[entry.__data__.resourceType] = [];
      }
      if (entry.attributes['x'].nodeValue <= xPos) {
        if ((lastObjects[entry.__data__.resourceType].length == 0) || (lastObjects[entry.__data__.resourceType][0].attributes['x'].nodeValue < entry.attributes['x'].nodeValue)) {
          var nonEnd = []
          if (entry.__data__.resourceType == "Condition") {
            nonEnd = lastObjects[entry.__data__.resourceType].filter(obj => obj.endX == chartWidth);
          }
          lastObjects[entry.__data__.resourceType] = [entry].concat(nonEnd);
        } else if ((lastObjects[entry.__data__.resourceType][0].attributes['x'].nodeValue == entry.attributes['x'].nodeValue) ||
                    (entry.__data__.resourceType == "Condition" && lastObjects[entry.__data__.resourceType][0].endX ==  entry.endX )){
          lastObjects[entry.__data__.resourceType].push(entry);
        }
      }
    });
    Object.keys(lastObjects).forEach(function(obj,indx) {
      lastObjectsPretty[obj] = ''
      lastObjects[obj].forEach( function (entryObj, indx) {
        var entry = d3.select(entryObj)[0][0];
        var header1Text = "</br>Status: " +  findStatus(entry.__data__) +
                      "</br>Description: " + findDescription(entry.__data__) +
                      "</br>Date: " + findStartDate(entry.__data__) +"</br></br>";
        lastObjectsPretty[obj] += header1Text;
      })
    });
    summary_onClick(lastObjectsPretty)
}
// ============================================================
function createPatientLegend(patientDict) {
    $("#patInfoPlaceholder").html('<h4>Patient Information</h4><ul class="columns"">');
    Object.keys(patientDict).forEach(function(key) {
        if (patDict.indexOf(key) != -1) {
            var dataObject = patientDict[key]
            if (key == "name") {
                if (Array.isArray(dataObject))  {
                  dataObject = dataObject[0]
                }
                dataString = `${dataObject['prefix']} ${dataObject['given']} ${dataObject['family']}`
                if  (patientDict[key].length > 1) {
                  dataString = dataString + `(nee ${dataObject['family']})`
                }
            } else if (key == "address") {
                dataString = '<ul class="columns">'
                dataObject.forEach(function(addr) {
                  dataString += `<li>${addr["line"]} ${addr["city"]},${addr["state"]} ${addr["postalCode"]}` + '</li>'
                })
                dataString += '</ul>'
            } else {
              dataString = JSON.stringify(dataObject);
            }
            $("#patInfoPlaceholder ul").append("<li>"+key+":"+dataString+"</li>")
        }
    });
    $("#patInfoPlaceholder").append("</ul>")
}
function createLegend() {
  $("#legend_placeholder svg").empty()
  $("#timeCtl").empty()
  var legend = legendShapeChart.append("svg:g").selectAll("g.legend")
    .attr("transform", function(d, i) {return "translate(" + (i * 125) +",30)"; }).append("path")
            .style("stroke", "black")
            .attr("r", 1e-6)
    .data(Object.keys(SynVisitDict))
    .enter()

  legend.append("g:rect")
    .attr("y", function(d,i) { return 40 + (75 *i)})
    .attr("width", 30)
    .attr("height", 70)
    .attr("class", function(d) {return "legend"})
    .attr("x", 0)
    .attr("fill",function(d) {return SynVisitDict[d].color;})
    .on("click", function(d) {
      var deactivate = false;
      // Find elements that are active to deactivate
      d3.select('#legend_placeholder').selectAll('.active')
      .attr("fill", function(element) {
        var returnColor  = SynVisitDict[element].color;
        if(d === element ) { deactivate= true; returnColor = "gray" }
        return returnColor
      }).attr("class", function(element) {
        var classVal = "active"
        if(d === element ) { deactivate= true;classVal = "" }
        return classVal
      })
      if (!deactivate) {
        // find "non-active" elements to activate
        d3.select('#legend_placeholder').selectAll('rect:not(.active)')
        .attr("fill", function(element) {
          var returnColor  = "gray";
          if(d === element ) { returnColor = SynVisitDict[d].color }
          return returnColor
        }).attr("class", function(element) {
          var classVal = ""
          if(d === element ) { classVal = "active"}
          return classVal
        })
      }
      selectValue = d3.select('#vivSelect').property('value')
      shownTypes = d3.select('#legend_placeholder').selectAll(".active").data()
      if  (shownTypes.length === 0) {
        d3.select('#legend_placeholder').selectAll('rect').attr("fill", function(element) { return SynVisitDict[element].color});
        shownTypes= Object.keys(SynVisitDict)
      }
      resetMenuFile(currentJSON.entry,
                    brush.extent()[0],
                    brush.extent()[1],
                   shownTypes)
    });
  legend.append("text")
    .text(function(d) { return(d) })
    .attr("transform", function(d,i) {var  yval =40 + (75*i); return `translate(40,${yval})rotate(90)` })
    .style("font-size", "8.5px");

  var legend = d3.select('#legend_placeholder').selectAll("svg")
  legend.append("text")
          .attr("x", 0)
          .attr("y", 20 )
          .attr("text-anchor", "left")
          .style("font-size", "12px")
          .text("Color Legend")

      // Creating the legend control
      ctrlX = d3.time.scale()
        .domain([new Date(start), new Date(stop)])
        .range([0, 750])
        .clamp(true);
      // Generate the xAxis for the above scale
      var brush = d3.svg.brush()
        .x(ctrlX)
        .extent([new Date(start), new Date(stop)])
        .on("brushend", ctlZoomFunc);
      function ctlZoomFunc() {
          $("#loadWheel").show(0)
          var value = brush.extent()[0];
          shownTypes = d3.select('#legend_placeholder').selectAll(".active").data()
          if  (shownTypes.length === 0) {
            d3.select('#legend_placeholder').selectAll('rect').attr("fill", function(element) { return SynVisitDict[element].color});
            shownTypes= Object.keys(SynVisitDict)
          }
          var header1Text = "Date: " + ctrlX.invert(d3.event.sourceEvent.x);
          $('#ctrlToolTip div').html(header1Text);
          d3.select("#ctrlToolTip").style("left", (d3.event.sourceEvent.pageX + 0) + "px")
                  .style("top", (d3.event.sourceEvent.pageY - 0) + "px")
                  .style("opacity", ".9");
          d3.select(".extent").attr("height","7px").style("fill","steelblue")
          var filteredObjs = currentJSON.entry.filter(entry =>  (entry.resource.resourceType == "Condition") || ((brush.extent()[0] <= new Date(findStartDate(entry.resource))) &&
                                                                (new Date(findStartDate(entry.resource)) < brush.extent()[1])))
          resetMenuFile(filteredObjs,
                    brush.extent()[0],
                    brush.extent()[1],
                   shownTypes)
      }
      var axisTimeControl = d3.select('#timeCtl').insert("svg")
                      .attr("height", 50)
                      .attr("width",1500)
                      .insert('g')
                      .attr("height", 50)
                      .attr("width",1500)
                      .attr('class', 'x axis chart')
                      .on('mousemove', function(d) {
                          var header1Text = "Date: " + ctrlX.invert(d3.event.x);
                          $('#ctrlToolTip div').html(header1Text);
                          d3.select("#ctrlToolTip").style("left", (d3.event.pageX + 0) + "px")
                                  .style("top", (d3.event.pageY - 0) + "px")
                                  .style("opacity", ".9");
                      }).on('mouseout', function(d) {
                          $('#ctrlToolTip div').html();
                          d3.select("#ctrlToolTip").style("opacity", "0");
                      })

      var ctrlXAxis = d3.svg.axis()
        .scale(ctrlX)
        .orient('middle')
        .tickSize(10)
        .tickPadding(8);
      var sortedConditions = Object.keys(longCondition).sort(function(a,b) {
          var dateA = new Date(a);
          var dateB = new Date(b);
          if (dateA < dateB ) {
            return -1;
          }
          if (dateA > dateB ) {
            return 1;
          }
          return 0;
      })
      var activityLine = d3.svg.line()
                           .x(function(d) { return ctrlX(new Date(d))})
                          .y(function(d) { return -longCondition[d] * 1.25})
                           .interpolate("step-after");
      axisTimeControl.insert("path")
                     .attr("class","line histo")
                     .attr('d', activityLine(sortedConditions));
      var activityHisto = d3.layout.histogram()
                            .bins(ctrlX.ticks(d3.time.week,1))
                            .value(function(d) {return new Date(d)})
      d3.select('#timeCtl').select('g').selectAll('.histo').data(activityHisto(dateArray)).enter().append('rect')
                     .attr('fill',"firebrick")
                     .attr('x', 1)
                     .attr('width', 1)
                     .attr('height', function(d) { return d.y} )
                     .attr("transform", function(d) { return "translate("+ctrlX(d.x)+",-"+ d.y+")"})
      axisTimeControl.call(ctrlXAxis)
                     .attr("transform", "translate(0,25)");
      axisTimeControl.call(brush);
}
//d3.select("#legend_placeholder").datum(null).call(legendShapeChart);
//createLegend();

try {
    var json = <?php
      $jsonText = file_get_contents("php://input");
      if ($jsonText == "")
        $jsonText =  '""';
      echo $jsonText;
      ?>;
    resetMenuFile(json.entry,start,stop, Object.keys(SynVisitDict))
    createLegend()
    currentJSON=json
} catch (error) {    $("#loadWheel").hide()
}
    </script>
  </body>
</html>
