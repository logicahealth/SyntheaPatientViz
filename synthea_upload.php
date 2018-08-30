<!DOCTYPE html>
<html>
  <title>Synthea Patient Display</title>
  <head>
  <meta http-equiv="Content-Type" content="text/html;charset=utf-8"/>
  <!-- Latest compiled and minified JavaScript -->
  <script src="//ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>
  <script src="//maxcdn.bootstrapcdn.com/bootstrap/3.2.0/js/bootstrap.min.js"></script>
  <script src="//d3js.org/d3.v3.min.js"></script>
  <script src="//d3js.org/d3-queue.v3.min.js"></script>
  <link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap.min.css"/>
  <script src="//ajax.googleapis.com/ajax/libs/jqueryui/1.11.0/jquery-ui.min.js"></script>
  <link rel="stylesheet" href="//ajax.googleapis.com/ajax/libs/jqueryui/1.11.0/themes/smoothness/jquery-ui.css"/>
  <link rel="stylesheet" href="//code.jquery.com/ui/1.11.0/themes/smoothness/jquery-ui.css"/>
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
  </div>
  <div id="descrHeader" ></div>
   <div>

    <label > Select synthetic patient file:</label>
    <select id="vivSelect"></select>
  <script type="application/javascript">

    var files = "<?php  foreach(glob('./local/*.json') as $filename) { echo $filename.",";  };?>"
    filesArray = files.split(",")
    filesArray.pop()
    for( var localFile in filesArray) {
      $("#vivSelect").append("<option val='"+filesArray[localFile]+"'>"+filesArray[localFile]+"</option>");
    }
  </script>
  </br>
    <label title="Set the data parameters for the timeline.">Select date range to view:</label>
    <input type="text" id="timeline_date_start" >
    <input type="text" id="timeline_date_stop">
    <button id="timeline_date_update">Update</button>
    <button id="timeline_date_reset">Reset</button>
    <div id="patInfoPlaceholder"/>
  </div>
  <div id="legend_placeholder" style="position:relative; left:20px;"></div>


<div id="dialog-modal" style="display:none;">
        <div id='filteredObjs'></div>
    </div>
 </div>
<div id="treeview_placeholder"/>
  <svg class="chart" ></svg>
<script type="application/javascript">
var margin = {top: 40, right: 40, bottom: 40, left:40},
        width = 1500,
        height =750;
var originalTransform = [40,60];

var visitDict = { "DiagnosticReport": {"color": "red", "height":0,"sd": "effectiveDateTime" ,"ed":"","desc":"code"},
	"Claim": {"color": "orange", "height":.1,"sd": "billablePeriod" ,"ed":"billablePeriod","desc":"total"},
	"MedicationRequest": {"color": "yellow", "height":.2,"sd": "authoredOn" ,"ed":"","desc":"medicationCodeableConcept"},
	"Encounter": {"color": "green", "height":.3,"sd": "period" ,"ed":"period","desc":"type"},
	"Observation": {"color": "blue", "height":.4,"sd": "issued" ,"ed":"","desc":"code"},
	"CarePlan": {"color": "purple", "height":.5,"sd": "period" ,"ed":"period","desc":"category"},
	"Procedure": {"color": "steelblue", "height":.6,"sd": ["performedDateTime", "performedPeriod"] ,"ed":["performedDateTime", "performedPeriod"],"desc":"code"},
	"Condition": {"color": "black", "height":.7,"sd": "onsetDateTime" ,"ed":"abatementDateTime","desc":"code"},
	"Immunization": {"color": "pink", "height":.8,"sd": "date" ,"ed":"","desc":"vaccineCode"},
}

var patDict = ["name","gender","birthDate","address"]
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
var existingConds = [];
var condDepthMax = 1
var endDate = "12/31/"+ currentDate.getFullYear()
$("#timeline_date_start").datepicker()
$("#timeline_date_stop").datepicker()
var organizationDict = {};
var organizationColorDict = {};
var legendShapeChart = d3.select('#legend_placeholder').insert("svg")
                .attr("height", 70)
                .attr("width",1500)
//Taken from http://bl.ocks.org/robschmuecker/6afc2ecb05b191359862
// =================================================================
var panSpeed = 200;

var isZoomed=false;
function zoomFunc() {
    var svg = d3.select('#treeview_placeholder').select('g')
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
    resetMenuFile(currentJSON, $("#timeline_date_start")[0].value, $("#timeline_date_stop")[0].value,Object.keys(visitDict))
  }
})

/*
*  Function to handle the resetting of the time scale.
*  Clears the values of the two date boxes and redraws the
*  graph, keeping the same package, and uses the default values
*  for date selection
*/

$("#timeline_date_reset").click( function() {
  $("#timeline_date_start")[0].value = ""
  $("#timeline_date_stop")[0].value = ""
  d3.select('#legend_placeholder').selectAll("svg").selectAll("*").attr("class","")
  createLegend();
  resetMenuFile(currentJSON,"","",Object.keys(visitDict))
})

/*
*  Function which is called when each box in the chart has the mouse
*  hovering over it.  It generates the tooltip and positions it at
*  the location of the mouse event.
*/
function rect_onMouseOver(d) {
  var header1Text = "Type: " + d.resourceType +
                    "</br>Status: " + d.status +
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
          position : {my: "top center", at: "top center", of: $("#treeview_placeholder")},
          width: 700,
          modal: true,
          title: "Filtered Object Information",
          open: function (event) {
           $("#filteredObjs").empty();
            Object.keys(d).forEach(function(key) {
                var objectData = d[key]
                if(key === "serviceProvider") {
                    objectData = organizationDict[d.serviceProvider.reference.substr(9)]
                }
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
          position : {my: "top center", at: "top center", of: $("#treeview_placeholder")},
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


function findStartDate(d) {
  var startDate=''
  var dateKeys = [visitDict[d.resourceType]["sd"]]
  if (typeof visitDict[d.resourceType]["sd"] == 'object') {
    dateKeys = visitDict[d.resourceType]["sd"];
  }
  dateKeys.forEach(function(dateKey) {
  if (Object.keys(d).indexOf(dateKey) != -1) {
      startDate = d[dateKey]
      if (typeof d[dateKey] == 'object') {
         startDate = d[dateKey]['start'];
      }
    }
  })
  return startDate
}
function findStopDate(d) {
  var stopDate = endDate
  if(visitDict[d.resourceType]["ed"] == "") {
   return 3;
  }
  var dateKeys = [visitDict[d.resourceType]["ed"]]
  if (typeof visitDict[d.resourceType]["ed"] == 'object') {
    dateKeys = visitDict[d.resourceType]["ed"];
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

function findDescription(d) {
  var descObj = d[visitDict[d.resourceType]["desc"]];
  var description = descObj["text"]
  if (description == undefined) {
     if (Object.keys(descObj).indexOf("value") != -1) {
        description = descObj["value"] + descObj['code'];
     } else {
        description = descObj[0]["text"]
     }
  }
  return description;
}
  d3.select("#vivSelect").on("change", function(){
    d3.json(d3.select('#vivSelect').property('value'), function(json) {
        currentJSON=json;
        resetMenuFile(json,"","", Object.keys(visitDict))
        createLegend();
    });
  });

/*
*  Main function to set up the scales and objects necessary to show
*  the install information
*/
function resetMenuFile(json,start,stop,filterList) {
  condDepthMax = 0
  existingConds = []

  //Read in the INSTALL JSON file
    /*
    *  Capture the package specific information.  The start date
    *  of the scale should be the install date of the first patch
    *  The end date is set to be some time in the future.
    *  TODO: Add a more specific date to the end.
    */
    var ptInfoArray = [];
    json["entry"].forEach(function (val, indx) {
      if (filterList.indexOf(val["resource"]['resourceType']) != -1) {
        ptInfoArray.push(val["resource"])
      } else if (val["resource"]['resourceType'] == "Organization") {
         if (Object.keys(organizationDict).indexOf(val["resource"]['id']) == -1) {
             organizationDict[val["resource"]['id']] = val["resource"]
             organizationColorDict[val["resource"]['identifier'][0]["value"]] = colors(indx)
         }
      } else if (val["resource"]['resourceType'] == "Patient") {
          patInfo = val["resource"];
      }
    })
    createPatientLegend(patInfo);
    //ptInfoArray.sort(function(a,b) {console.log(a); return a.issued.localeCompare(b.issued); });
    if (start === "") {start = findStartDate(ptInfoArray[0])}
    if (stop === "") { stop = endDate}
    var svg = d3.select('#treeview_placeholder').select('svg')
    svg.selectAll("*").remove();
    $("#timeline_date_start").datepicker("setDate",new Date(start))
    $("#timeline_date_stop").datepicker("setDate",new Date(stop))

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
            var xVal =(d3.event.x-matches[1])/scale
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
    var svgG = d3.select('#treeview_placeholder').select('svg').select('g')
    svgG.selectAll('.rect')
      .data(ptInfoArray)
      .enter().append('rect')
      .attr('class', 'bar')
      .attr('x', function(d) {return x(new Date(findStartDate(d))); })
      .attr('width', function(d) {
          var yCoord = height * visitDict[d.resourceType]["height"]
          if (d.resourceType === "Condition"){
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
          if (d.resourceType === "Condition"){
              var heightVal = (height * .095)/(condDepthMax+1)
              return (height * visitDict[d.resourceType]["height"]) + (heightVal * d.depthVal);
          } else {
            var yCoord = height * visitDict[d.resourceType]["height"]
            d.originalY = yCoord
            return yCoord
          }
      })
      .attr('originalY', function(d) { return d.originalY})

      .attr('height', function(d) {
          // Check to see if existing objects need to be shrunk
          var xCoord = x(new Date(findStartDate(d)))
          var yCoord = height * visitDict[d.resourceType]["height"]
          var existingBars;
          if (d.resourceType === "Condition"){
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
      .attr("fill", function(d,i) {return visitDict[d.resourceType]["color"]; })
      .attr('stroke', function(d,i) {
          var color = "white";
          if (d.serviceProvider) {
            color = organizationColorDict[organizationDict[d.serviceProvider.reference.substr(9)]['identifier'][0]["value"]]
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
};

function findLastObjects(xPos) {
    var lastObjects = {}
    var lastObjectsPretty = {}
    d3.selectAll("rect.bar")[0].forEach(function(obj,i) {
      var entry = d3.select(obj)[0][0];
      console.log(entry);

      if (entry.attributes['x'].nodeValue <= xPos) {
        // "Totally different objects: replace entry with new value
        if ((Object.keys(lastObjects).indexOf(entry.__data__.resourceType) == -1) ||
           (lastObjects[entry.__data__.resourceType][0].attributes['x'].nodeValue < entry.attributes['x'].nodeValue)) {
            lastObjects[entry.__data__.resourceType] = [entry]
        }
          //same objects on the same day: stack in array
        else if (lastObjects[entry.__data__.resourceType][0].attributes['x'].nodeValue == entry.attributes['x'].nodeValue) {
            lastObjects[entry.__data__.resourceType].push(entry);
        }
      }
    });
    Object.keys(lastObjects).forEach(function(obj,indx) {
      lastObjectsPretty[obj] = ''
      lastObjects[obj].forEach( function (entryObj, indx) {
        var entry = d3.select(entryObj)[0][0];
        var header1Text = "</br>Status: " + entry.__data__.status +
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
            var objectData = JSON.stringify(patientDict[key])
            if (key == "name") {
               objectData = `${patientDict[key][0]['prefix']} ${patientDict[key][0]['given']} ${patientDict[key][0]['family']}`
               if  (patientDict[key].length > 1) {
                 objectData = objectData + `(nee ${patientDict[key][1]['family']})`
               }
            } else if (key == "address") {
                objectData = '<ul class="columns">'
                patientDict[key].forEach(function(addr) {
                  objectData += `<li>${addr["line"]} ${addr["city"]},${addr["state"]} ${addr["postalCode"]} </li> `
                })
                objectData += '</ul>'
            }
            $("#patInfoPlaceholder ul").append("<li>"+key+":"+objectData+"</li>")
        }
    });
    $("#patInfoPlaceholder").append("</ul>")
}
function createLegend() {
  var legend = legendShapeChart.append("svg:g").selectAll("g.legend")
    .attr("transform", function(d, i) {return "translate(" + (i * 125) +",30)"; }).append("path")
            .style("stroke", "black")
            .attr("r", 1e-6)
    .data(Object.keys(visitDict))
    .enter()

  legend.append("g:rect")
    .attr("y", 40)
    .attr("width", 120)
    .attr("height", 20)
    .attr("class", function(d) {return "legend"})
    .attr("x", function(d,i) { return 0 + (150*i)})
    .attr("fill",function(d) {return visitDict[d].color;})
    .on("click", function(d) {
      var deactivate = false;
      // Find elements that are active to deactivate
      d3.select('#legend_placeholder').selectAll('.active')
      .attr("fill", function(element) {
        var returnColor  = visitDict[element].color;
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
          if(d === element ) { returnColor = visitDict[d].color }
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
        createLegend();
        shownTypes= Object.keys(visitDict)
      }
      resetMenuFile(currentJSON,
                $("#timeline_date_start")[0].value,
                $("#timeline_date_stop")[0].value,
               shownTypes)
    });
  legend.append("text")
    .text(function(d) { return(d) })
    .attr("y", 35)
    .attr("x", function(d,i) { return 0 + (150*i)})

  var legend = d3.select('#legend_placeholder').selectAll("svg")
  legend.append("text")
          .attr("x", 0)
          .attr("y", 20 )
          .attr("text-anchor", "left")
          .style("font-size", "16px")
          .text("Color Legend")
}
//d3.select("#legend_placeholder").datum(null).call(legendShapeChart);
createLegend();

try {
    var json = <?php
      $jsonText = file_get_contents("php://input");
      if ($jsonText == "")
        $jsonText =  '""';
      echo $jsonText;
      ?>;
    resetMenuFile(json,"","", Object.keys(visitDict))
    currentJSON=json
} catch (error) {console.log(error)
}
    </script>
  </body>
</html>
