<?php

//THIS FUNCTION IS USED TO CREATE THE OBJECT CHART THAT  WILL BE USED TO DRAW LITTLE CHARTS

class chart_small_line  {

    private $id_chart;
    private $width = 200;
    private $height = 45;
    private $points_number = 50;
    private $results;
    private $outage;
    private $average;
    private $reply_max;
    private $reply_min;
    private $reply_avg;
    private $reply_good;
    private $full_script;


    function setIDChart ($id) {
        if ($id == "") {
            $id = "Chart" . microtime(true)*10000;
        }
        $this->id_chart = $id;
    }

    function setWidth ($w) {
        $this->width = $w;
    }

    function setHeight ($h) {
        $this->height = $h;
    }

    static function getGlobalSettings () {
        return "Chart.defaults.global.animation = false;
                     Chart.defaults.global.showTooltips = false;
                     Chart.defaults.global.scaleShowLabels = false;
                     Chart.defaults.global.responsive = false;
                     Chart.defaults.global.maintainAspectRatio = false;
                     Chart.defaults.global.showScale = false;";
    }

    static function getLineChartSettings () {
        return  "Chart.defaults.Line.pointDot = false;
                     Chart.defaults.Line.pointDotRadius = 1;
                     Chart.defaults.Line.scaleShowGridLines = false;
                     Chart.defaults.Line.datasetStroke = false;
                     Chart.defaults.Line.datasetFill = true;
                     Chart.defaults.Line.datasetStrokeWidth = 1;
                     Chart.defaults.Line.fillColor = 'rgba(220,220,220,0.2)';
                     Chart.defaults.Line.bezierCurve = true;
                     Chart.defaults.Line.bezierCurveTension = 0.2;";
    }

    function getDatasetOptionResults () {
        return "fillColor : 'rgba(220,220,220,0.2)',
                    strokeColor: 'rgba(151,187,205,1)'";
    }

    function getDatasetOptionOutage () {
       return  "fillColor : 'rgba(255,0,0,0.5)',
                    strokeColor: 'rgba(255,0,0,0.2)'";
    }

    function getDatasetOptionAverage () {
        return  "fillColor : 'rgba(0,128,0,0)',
                    strokeColor: 'rgba(0,128,0,0.3)'";
    }


    function setPointsNumber ($n) {
        $this->points_number = $n;
    }

    function getEmptyLabels () {
        $label_str = "";
        for ($i=0;$i<$this->points_number;$i++) {
            $label_str .= "'',";
        }

        //RETURN LABEL STRING WITHOUT LAST ","
        return substr($label_str,0,-1);
    }

    function setRawResults ($raw) {
        //var_dump($raw);exit;
        $this->results = explode("@",$raw);
        $this->results = array_slice($this->results,-($this->points_number));

    }

    function setResultString () {
        $this->results = implode(",",$this->results);
    }

    function calcMinMaxAvg () {



        $this->reply_max = 0;
        $this->reply_min = 99999;
        $this->reply_avg = 0;
        $this->reply_good = 0;
        foreach ($this->results as $value) {

            $value = floatval($value);

            if ($value != 0) {
                $this->reply_good++;
                if ($value > $this->reply_max) {
                    $this->reply_max = $value;
                }
                if ($value < $this->reply_min) {
                    $this->reply_min = $value;
                }
                $this->reply_avg = $this->reply_avg + $value;

            } //END IF GOOD VALUE;
        } //END FOREACH

        if ($this->reply_good > 0) {
            $this->reply_avg = $this->reply_avg / $this->reply_good;
        }


    }

    function calcOutageSerie () {

        $outage_value = $this->reply_max * 1.2;
        $i = 0;
        foreach ($this->results as $value) {
            if ($value == 0) {
                $this->outage[$i] = $outage_value;
            } else {
                $this->outage[$i] = 0;
            }
            $i++;
        } //END FOREACH

        $this->outage = implode(",",$this->outage);
    }

    function setAverage () {
        $n = count($this->results);
        $this->average = array_fill(0,$n,$this->reply_avg);
        $this->average = implode(",",$this->average);
    }

    function getAverage () {
        return $this->reply_avg;
    }
    function generateScript () {

        $this->calcMinMaxAvg();
        $this->calcOutageSerie();
        $this->setAverage();


        //LAST BEFORE CREATING STRING....
        $this->setResultString();

        $script = "";

        $script .= "<canvas id='myChart".$this->id_chart ."' width='".$this->width."px' height='".$this->height."px'></canvas>";

        $script .="<script>";

        $script .= "var ctx = document.getElementById('myChart". $this->id_chart ."').getContext('2d');";

        $script .= "var myNewChart = new Chart(ctx).Line({";

                $script .= " labels: [" . $this->getEmptyLabels() . "],";

                $script .= "datasets : ["; //START DATA SET

                        $script .= "{";  //START FIRST SERIE (RESULTS)

                                $script .= $this->getDatasetOptionResults() . ",";

                                $script .= "data: [" . $this->results . "]";

                        $script .= "}"; //END FIRST SERIE

                        $script .= ",{"; //START SECOND SERIE (OUTAGE)

                                $script .= $this->getDatasetOptionOutage() . ",";

                                $script .= "data: [" . $this->outage . "]";

                        $script .= "}";  //END SECOND SERIE

                        $script .= ",{"; //START THIRD SERIE (AVERAGE)

                        $script .= $this->getDatasetOptionAverage() . ",";

                        $script .= "data: [" . $this->average . "]";

                        $script .= "}";  //END THIRD SERIE

                $script .= "]"; //DATASET END...
        $script .= "});";

        $script .= "</script>";

        $this->full_script = $script;

    }

    function getScript () {
        return $this->full_script;
    }

}