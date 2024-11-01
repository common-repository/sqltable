<?php
/*
Plugin Name: Sql Table Plugin
Plugin URI: http://www.morazain.com/sqltable
Tags: create table from query
Description: Creates a table from a query. short code format is [sqltable  query ="your select statement" columns="list of columns" headers="list of headers" ...] see http://www.morazain.com/sqltable for details
Version: 1.0.0
Author: Guy Morazain
Author URI: http://www.morazain.com
*/
class sqlTableColumn
    {
    var $name;   // column name
    var $type;   // One of {I,S,F,D} Interger, String Float or Date
    var $format; // sprintf format or if date field dateFormat
    var $style;

    function sqlTableColumn($n, $t = "S", $f = "%s", $s = '')
        {
        $this->name=$n;
        $this->type=$t;
        $this->format=$f;
        $this->style=$s;
        }

    function val($r)
        {
        //        $s="\$v = \$r->" . $this->name . ";";
        //        eval($s);
        $v=getColumnValue($r, $this->name);

        if ($this->type == "D")
            {
            $date=new DateTime($v);
            return $date->format('Y-m-d');
            }
        return sprintf($this->format, $v);
        }
    }

class sqlTable
    {

    var $head;
    var $foot;
    var $even;
    var $odd;
    var $class;
    var $query;
    var $columns;
    var $headers;
    var $footers;

    function sqlTable($head = '', $foot='',$even = '', $odd = '', $class = '', $query = '', $columns = '', $headers = '',
        $footers = '')
        {

        $this->head=$head;
        $this->foot=$foot;
        $this->even=$even;
        $this->odd=$odd;
        $this->class=$class;
        $this->query=$query;
        $this->columns=$this->getColumns($columns);
        $this->headers=$this->getHeaders($headers);
        $this->footers=$this->getFooters($footers);
        }

    function getColumns($c)
        {
        $columns=array();
        $rawColumns=explode(";", $c);
        $cnt=0;

        foreach ($rawColumns as $col)
            {

            $raw = explode("|", $col);
            $num=count($raw);

            if ($num < 3 || $num > 4)
                return new sqlTableColumn("ERROR: element " . ($cnt + 1)
                    . " [ $col] malformed. pattern is name|type|format{|css class}", "X", "0");

            if ($raw[1] != "I" && $raw[1] != "S" && $raw[1] != "D" && $raw[1] != "F")
                return new sqlTableColumn("ERROR: element " . ($cnt + 1) . " [ $col ] type [" . $raw[1]
                    . "] must be one or I,F,D or S", "X", "0");
            $style='';

            if ($num == 4)
                $style=$raw[3];

            $columns[$cnt]=new sqlTableColumn($raw[0], $raw[1], $raw[2], $style);
            $cnt++;
            }
        return $columns;
        }

    function getHeaders($h)
        {
        $headers=array();
        $rawHeaders=explode(";", $h);
        $cnt=0;

        foreach ($rawHeaders as $head)
            {

            $raw = explode("|", $head);
            $num=count($raw);
            $style='';
            $name=$raw[0];

            if ($num == 2)
                $style=$raw[1];

            $headers[$cnt]=array
                (
                $name,
                $style
                );

            $cnt++;
            }
        return $headers;
        }

    function getFooters($f)
        {
        $footers=array();
        $rawfooters=explode(";", $f);
        $cnt=0;

        foreach ($rawfooters as $foot)
            {
			global $msg;
            $raw = explode("|", $foot);
            $num=count($raw);
            $style='';
            $column='nada';
            $type='S';
            $format='%s';
            $name=$raw[0];

            if ($num > 1)
                $style=$raw[1];

            if ($num > 2)
                $column=$raw[2];
            if ($num > 3)
                $type=$raw[3];
            if ($num > 4)
                $format=$raw[4];

            $footers[$cnt]=new sqlTableFooter (
                $name,
                $style,
                $column,
                $type,
                $format
                );
			$msg.=" $cnt $name $style $column<br>";
            $cnt++;
            }
        return $footers;
        }
    }
    class sqlTableFooter {
    var $name;
    var $style;
    var $col;
    var $val;
    var $format;
    function sqlTableFooter ($n,$s,$c,$t,$f){
      $this->name=$n;
      $this->style=$s;
      $this->col=$c;
      $this->type=$t;
      $this->format=$f;
      $this->val='';
    }
        function value()
	        {
	        $v=$this->val;

	        if ($this->type == "D")
	            {
	            $date=new DateTime($v);
	            return $date->format('Y-m-d');
	            }
	        return sprintf($this->format, $v);
        }
    }

function getColumnValue($r, $n)
    {
    $s="\$v = \$r->" . $n . ";";
    eval($s);
    return $v;
    }

function compTableFooters($r, $footers)
    {
    global $msg;

    foreach ($footers as $footer)
        {
        if ($footer->name == "@min")
            {
            $v=getColumnValue($r, $footer->col);
            if ($footer->val == '')
                $footer->val=$v;

            else if ($footer->val> $v)
                $footer->val=$v;
            }
        else if ($footer->name == "@max")
            {
            $v=getColumnValue($r, $footer->col);
            if ($footer->val == '')
                $footer->val=$v;

            else if ($footer->val< $v)
                $footer->val=$v;
            }
        else if ($footer->name == "@sum")

            {
            $v=getColumnValue($r, $footer->col);
            $footer->val+=$v;

            }
        }
    }

function showTableFooters($footers,$style)
    {
    global $msg;

    $buf="<tr class='".$style."'>";

    foreach ($footers as $f)
        {
		$msg.=     count($f).":".$f->name."<br>";

        if ($f->name >'' && substr($f->name, 0, 1) == "@")
            $buf.="<td class='" . $f->style . "'>" . $f->value() . "</td>";
        else
            $buf.="<td class='" . $f->style . "'>" . $f->name . "</td>";
        }
    return $buf . "</tr>";
    }

function sqlTable_func($atts)
    {
    global $wpdb, $wp_locale, $msg;
    $msg='';
    extract(shortcode_atts(array
        (
        'head' => '',
        'foot' => '',
        'even' => '',
        'odd' => '',
        'class' => '',
        'query' => '',
        'columns' => '',
        'headers' => '',
        'footers' => ''
        ), $atts));

    $report=new sqlTable($head, $foot,$even, $odd, $class, $query, $columns, $headers, $footers);

    $buf='';
    $buf.="<table";

    if ($report->class != '')
        $buf.=" class='" . $report->class . "' ";
    $buf.="><tr";

    if ($report->head != '')
        $buf.=" class='" . $report->head . "' ";
    $buf.=">";

    foreach ($report->headers as $hd)
        {
        $buf.="<td ";

        if ($hd[1] != '')
            $buf.=" class='" . $hd[1] . "'";

        $buf.=">" . $hd[0] . "</td>";
        }
    $buf.="</tr>";
    $cnt=0;

    $rows=$wpdb->get_results($report->query);
    if(is_array($rows)){
       for($i=0;$i<count($rows);++$i){
        $row=$rows[$i];
        compTableFooters($row, $report->footers);
        $buf.="<tr";

        if ($cnt % 2 == 0 && $report->odd != '')
            $buf.=" class='" . $report->odd . "' ";

        if ($cnt % 2 == 1 && $report->even != '')
            $buf.=" class='" . $report->even . "' ";

        $buf.=">";

        foreach ($report->columns as $col)
            {
            $buf.="<td ";

            if ($col->style != '')
                $buf.=" class='" . $col->style . "' ";

            $buf.=">" . $col->val($row) . "</td>";
            }
        $buf.="</tr>";
        $cnt++;
        }
    }
    $buf.=showTableFooters($report->footers,$report->foot);
    return $buf . "</table>";
    }
add_shortcode('sqltable', 'sqlTable_func');
?>