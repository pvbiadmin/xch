<?php

namespace BPL\Ajax\Ajaxer\Genealogy;

require_once 'bpl/mods/helpers.php';

use function BPL\Mods\Url_SEF\sef;
use function BPL\Mods\Helpers\db;

/**
 * @param           $type
 * @param           $user_id
 * @param   string  $plan
 *
 * @return string
 *
 * @since version
 */
function main($type, $user_id, string $plan = 'binary_pair'): string
{
	$dir_font  = 'bpl/plugins/orgchart/assets/css/font-awesome.min.css';
	$dir_style = 'bpl/plugins/orgchart/assets/css/style.css';
	$dir_d3    = 'bpl/plugins/orgchart/assets/js/d3.min.js';

	$str = '<link rel="stylesheet" href="' . $dir_font . '">';
	$str .= '<link rel=\'stylesheet prefetch\' href=\'https://fonts.googleapis.com/css?family=Roboto\'>';
	$str .= '<link rel="stylesheet" href="' . $dir_style . '">';
	$str .= '<style>
    	#genealogy_' . $type . ' {
            cursor: move;
            height: 100%;
            width: 100%;
            background-color: transparent;
            border: 1px dashed #C3CCE8;
            margin: 0;
        };
	</style>';
	$str .= '<div id="genealogy_' . $type . '"></div>';
	$str .= '<script src="' . $dir_d3 . '"></script>';
	$str .= '<script>' . render($type, $user_id, $plan) . '</script>';

	return $str;
}

/**
 *
 * @return string[]
 *
 * @since version
 */
function plan_attr(): array
{
	return [
		'indirect_referral'  => 'bonus_indirect_referral',
		'unilevel'           => 'unilevel',
		'binary_pair'        => 'income_cycle',
		'leadership_binary'  => 'bonus_leadership',
		'leadership_passive' => 'bonus_leadership_passive',
		'matrix'             => 'bonus_matrix',
		'power'              => 'bonus_power',
		'matrix_table'       => 'bonus_share',
		'harvest'            => 'bonus_harvest'
	];
}

/**
 * @param $plan
 *
 * @return string
 *
 * @since version
 */
function details($plan): string
{
	$attr = set_attr($plan);

	$img = $plan !== 'binary_pair' ? '"active.png"' :
        '(d.caption === "Y" || d.caption === "X" ? "active.png" : "inactive.png")';
	$usr = $plan !== 'binary_pair' ? '"emp-name"' :
        '(d.caption === "Y" || d.caption === "X" ? "emp-name" : "inactive-binary")';

	$str = 'nodeGroup.append("text")
	            .attr("x", dynamic.nodeTextLeftMargin)
	            .attr("y", attrs.nodePadding + 10)
	            .attr("class", function (d) {
	                return ' . $usr . ';
	            })
	            .attr("text-anchor", "left")
	            .text(function (d) {
	                return d.username.trim();
	            })
	            .call(wrap, attrs.nodeWidth);' . "\n\n";

	$str .= 'nodeGroup.append("text")
	            .attr("x", dynamic.nodeTextLeftMargin)
	            .attr("y", dynamic.nodePositionNameTopMargin)
	            .attr("class", "emp-position-name")
	            .attr("dy", ".15em")
	            .attr("text-anchor", "left")	           
	            .text(function (d) {	
	                return d.account;
	            });' . "\n\n";

	$str .= 'nodeGroup.append("text")
            .attr("x", dynamic.nodeTextLeftMargin)
            .attr("y", attrs.nodePadding + 10 + dynamic.nodeImageHeight / 4 * 2)
            .attr("class", "emp-position-name")
            .attr("dy", "0.05em")
            .attr("text-anchor", "left")

            .text(function (d) {               
                return d.' . $attr . ';
            });' . "\n\n";

	$str .= $plan !== 'binary_pair' ? '' : 'nodeGroup.append("text")
	            .attr("x", dynamic.nodeTextLeftMargin)
	            .attr("y", dynamic.nodeChildCountTopMargin)
	            .attr("class", function (d) {
	                return ' . $usr . ';
	            })
	            .attr("dy", "-0.2em")
	            .attr("text-anchor", "left")
	
	            .text(function (d) {
	                switch(d.caption) {
	                    case "Y":
	                        return "Active";
	                    break;
	                    case "X":
	                        return "Reactivated";
	                    break;
	                    case "Z":
	                        return "Maxed Out";
	                    break;
	                    default:
	                        return "Inactive";
	                    break;
	                }	               
	            });' . "\n\n";

	$str .= 'nodeGroup.append("defs").append("svg:clipPath")
	            .attr("id", "clip")
	            .append("svg:rect")
	            .attr("id", "clip-rect")
	            .attr("rx", 3)
	            .attr("x", attrs.nodePadding)
	            .attr("y", 2 + attrs.nodePadding)
	            .attr("width", dynamic.nodeImageWidth)
	            .attr("fill", "none")
	            .attr("height", dynamic.nodeImageHeight - 4);' . "\n\n";

	$str .= 'nodeGroup.append("svg:image")
	            .attr("y", 2 + attrs.nodePadding)
	            .attr("x", attrs.nodePadding)
	            .attr("preserveAspectRatio", "yes")
	            .attr("width", dynamic.nodeImageWidth)
	            .attr("height", dynamic.nodeImageHeight - 4)
	            .attr("clip-path", "url(#clip)")
	            .attr("xlink:href", function (d) {
	                return params.imageUrl + ' . $img . ';
	            });' . "\n\n";

	return $str;
}

/**
 * @param           $type
 * @param           $user_id
 * @param   string  $plan
 *
 * @return string
 *
 * @since version
 */
function ajax($type, $user_id, string $plan = 'binary_pair'): string
{
	return 'jQuery.ajax({
	    type: "post",
	    dataType: "json",
	    url: "bpl/ajax/action.php",
	    data: {
	        "action": "genealogy_' . $type . '",
	        "id_user": ' . $user_id . ',
	        "plan": "' . $plan . '"
	    },
	    success: function (data) {
	        params = {
	            selector: "#genealogy_' . $type . '",
	            imageUrl: "bpl/plugins/orgchart/assets/img/",
	            profileUrl: "' . sef(44) . '",
	            chartWidth: window.innerWidth,
	            chartHeight: window.innerHeight,
	            data: data,
	            pristinaData: JSON.parse(JSON.stringify(data))
	        };
	
	        drawOrganizationChart(params);
	    }
	});';
}

/**
 * @param $type
 * @param $user_id
 * @param $plan
 *
 * @return string
 *
 * @since version
 */
function render($type, $user_id, $plan): string
{
	$str = 'var params = {};';
	$str .= ajax($type, $user_id, $plan);
	$str .= genealogy($plan);

	return $str;
}

/**
 * @param $plan
 *
 * @return string
 *
 * @since version
 */
function genealogy($plan): string
{
	return 'function drawOrganizationChart(params) {		
	    var attrs = {
	        EXPAND_SYMBOL: "\uf067",
	        COLLAPSE_SYMBOL: "\uf068",
	        selector: params.selector,
	        root: params.data,
	        width: params.chartWidth,
	        height: params.chartHeight,
	        index: 0,
	        nodePadding: 9,
	        collapseCircleRadius: 7,
	        nodeHeight: 80,
	        nodeWidth: 160,
	        duration: 750,
	        rootNodeTopMargin: 20,
	        minMaxZoomProportions: [0.05, 3],
	        linkLineSize: 180,
	        collapsibleFontSize: "10px",
	        userIcon: "\uf013",
	        nodeStroke: "#ccc",
	        nodeStrokeWidth: "1px"
	    };
	
	    var dynamic = {};
	
	    dynamic.nodeImageWidth = attrs.nodeHeight * 100 / 140;
	    dynamic.nodeImageHeight = attrs.nodeHeight - 2 * attrs.nodePadding;
	    dynamic.nodeTextLeftMargin = attrs.nodePadding * 2 + dynamic.nodeImageWidth;
	    dynamic.rootNodeLeftMargin = attrs.width / 2;
	    dynamic.nodePositionNameTopMargin = attrs.nodePadding + 8 + dynamic.nodeImageHeight / 4;
	    dynamic.nodeChildCountTopMargin = attrs.nodePadding + 14 + dynamic.nodeImageHeight / 4 * 3;
	
	    var tree = d3.layout.tree().nodeSize([attrs.nodeWidth + 40, attrs.nodeHeight]);
	    var diagonal = d3.svg.diagonal()
	        .projection(function (d) {
	            debugger;
	            return [d.x + attrs.nodeWidth / 2, d.y + attrs.nodeHeight / 2];
	        });
	    var zoomBehaviours = d3.behavior
	        .zoom()
	        .scaleExtent(attrs.minMaxZoomProportions)
	        .on("zoom", redraw);
	    var svg = d3.select(attrs.selector)
	        .append("svg")
	        .attr("width", attrs.width)
	        .attr("height", attrs.height)
	        .call(zoomBehaviours)
	        .append("g")
	        .attr("transform", "translate(" + (attrs.width / 2 - 190) + "," + 20 + ")");
	
	    //necessary so that zoom knows where to zoom and un-zoom from
	    zoomBehaviours.translate([dynamic.rootNodeLeftMargin, attrs.rootNodeTopMargin]);
	
	    attrs.root.x0 = 0;
	    attrs.root.y0 = dynamic.rootNodeLeftMargin;
	
	    if (params.mode !== "department") {
	        // adding unique values to each node recursively
	        var uniq = 1;
	
	        addPropertyRecursive("uniqueIdentifier", function () {
	            return uniq++;
	        }, attrs.root);
	    }
	
	    expand(attrs.root);
	
	    if (attrs.root.children) {
	        attrs.root.children.forEach(collapse);
	    }
	
	    update(attrs.root);
	
	    d3.select(attrs.selector).style("height", attrs.height);
	
	    function update(source, param) {
	        // Compute the new tree layout.
	        var nodes = tree.nodes(attrs.root)
	                .reverse(),
	            links = tree.links(nodes);
	
	        // Normalize for fixed-depth.
	        nodes.forEach(function (d) {
	            d.y = d.depth * attrs.linkLineSize;
	        });
	
	        // Update the nodes…
	        var node = svg.selectAll("g.node")
	            .data(nodes, function (d) {
	                return d.id || (d.id = ++attrs.index);
	            });
	
	        // Enter any new nodes at the parent\'s previous position.
	        var nodeEnter = node.enter()
	            .append("g")
	            .attr("class", "node")
	            .attr("transform", function () {
	                return "translate(" + source.x0 + "," + source.y0 + ")";
	            });
	
	        var nodeGroup = nodeEnter.append("g")
	            .attr("class", "node-group");
	
	        nodeGroup.append("rect")
	            .attr("width", attrs.nodeWidth)
	            .attr("height", attrs.nodeHeight)
	            .attr("data-node-group-id", function (d) {
	                return d.uniqueIdentifier;
	            })
	            .attr("class", function (d) {
	                var res = "";
	                if (d.isLoggedUser) res += "nodeRepresentsCurrentUser ";
	                res += d._children || d.children ? "nodeHasChildren" : "nodeDoesNotHaveChildren";
	
	                return res;
	            });
	
	        var collapsiblesWrapper =
	            nodeEnter.append("g")
	                .attr("data-id", function (v) {
	                    return v.uniqueIdentifier;
	                });
	
	        var collapsibles = collapsiblesWrapper.append("circle")
	            .attr("class", "node-collapse")
	            .attr("cx", attrs.nodeWidth - attrs.collapseCircleRadius)
	            .attr("cy", attrs.nodeHeight - 7)
	            .attr("", setCollapsibleSymbolProperty);
	
	        //hide collapse rect when node does not have children
	        collapsibles.attr("r", function (d) {
	            if (d.children || d._children) return attrs.collapseCircleRadius;
	
	            return 0;
	        })
	            .attr("height", attrs.collapseCircleRadius);
	
	        collapsiblesWrapper.append("text")
	            .attr("class", "text-collapse")
	            .attr("x", attrs.nodeWidth - attrs.collapseCircleRadius)
	            .attr("y", attrs.nodeHeight - 3)
	            .attr("width", attrs.collapseCircleRadius)
	            .attr("height", attrs.collapseCircleRadius)
	            .style("font-size", attrs.collapsibleFontSize)
	            .attr("text-anchor", "middle")
	            .style("font-family", "FontAwesome")
	            .text(function (d) {
	                return d.collapseText;
	            });
	
	        collapsiblesWrapper.on("click", click);' .

		details($plan)

		. '// Transition nodes to their new position.
	        var nodeUpdate = node.transition()
	            .duration(attrs.duration)
	            .attr("transform", function (d) {
	                return "translate(" + d.x + "," + d.y + ")";
	            });
	
	        //todo replace with attrs object
	        nodeUpdate.select("rect")
	            .attr("width", attrs.nodeWidth)
	            .attr("height", attrs.nodeHeight)
	            .attr("rx", 3)
	            .attr("stroke", function (d) {
	                if (param && d.uniqueIdentifier === param.locate) {
	                    return "#a1ceed";
	                }
	
	                return attrs.nodeStroke;
	            })
	            .attr("stroke-width", function (d) {
	                if (param && d.uniqueIdentifier === param.locate) {
	                    return 6;
	                }
	
	                return attrs.nodeStrokeWidth
	            });
	
	        // Transition exiting nodes to the parent\'s new position.
	        var nodeExit = node.exit().transition()
	            .duration(attrs.duration)
	            .attr("transform", function () {
	                return "translate(" + source.x + "," + source.y + ")";
	            })
	            .remove();
	
	        nodeExit.select("rect")
	            .attr("width", attrs.nodeWidth)
	            .attr("height", attrs.nodeHeight);
	
	        // Update the links…
	        var link = svg.selectAll("path.link")
	            .data(links, function (d) {
	                return d.target.id;
	            });
	
	        // Enter any new links at the parent\'s previous position.
	        link.enter().insert("path", "g")
	            .attr("class", "link")
	            .attr("x", attrs.nodeWidth / 2)
	            .attr("y", attrs.nodeHeight / 2)
	            .attr("d", function () {
	                var o = {
	                    x: source.x0,
	                    y: source.y0
	                };
	
	                return diagonal({
	                    source: o,
	                    target: o
	                });
	            });
	
	        // Transition links to their new position.
	        link.transition()
	            .duration(attrs.duration)
	            .attr("d", diagonal);
	
	        // Transition exiting nodes to the parent\'s new position.
	        link.exit().transition()
	            .duration(attrs.duration)
	            .attr("d", function () {
	                var o = {
	                    x: source.x,
	                    y: source.y
	                };
	
	                return diagonal({
	                    source: o,
	                    target: o
	                });
	            })
	            .remove();
	
	        // Stash the old positions for transition.
	        nodes.forEach(function (d) {
	            d.x0 = d.x;
	            d.y0 = d.y;
	        });
	
	        var x = 0;
	        var y = 0;
	
	        if (param && param.locate) {
	            nodes.forEach(function (d) {
	                if (d.uniqueIdentifier === param.locate) {
	                    x = d.x;
	                    y = d.y;
	                }
	            });
	
	            // normalize for width/height
	            new_x = window.innerWidth / 2 - x;
	            new_y = window.innerHeight / 2 - y;
	
	            // move the main container g
	            svg.attr("transform", "translate(" + new_x + "," + new_y + ")");
	            zoomBehaviours.translate([new_x, new_y]);
	            zoomBehaviours.scale(1);
	        }
	
	        if (param && param.centerMySelf) {
	            nodes.forEach(function (d) {
	                if (d.isLoggedUser) {
	                    x = d.x;
	                    y = d.y;
	                }
	            });
	
	            // normalize for width/height
	            var new_x = window.innerWidth / 2 - x;
	            var new_y = window.innerHeight / 2 - y;
	
	            // move the main container g
	            svg.attr("transform", "translate(" + new_x + "," + new_y + ")");
	            zoomBehaviours.translate([new_x, new_y]);
	            zoomBehaviours.scale(1);
	        }
	    }
	
	    // Toggle children on click.
	    function click(d) {
	        d3.select(this).select("text").text(function (dv) {
	            if (dv.collapseText === attrs.EXPAND_SYMBOL) {
	                dv.collapseText = attrs.COLLAPSE_SYMBOL
	            } else {
	                if (dv.children) {
	                    dv.collapseText = attrs.EXPAND_SYMBOL
	                }
	            }
	
	            return dv.collapseText;
	        });
	
	        if (d.children) {
	            d._children = d.children;
	            d.children = null;
	        } else {
	            d.children = d._children;
	            d._children = null;
	        }
	
	        update(d);
	    }
	
	    //########################################################
	
	    //Redraw for zoom
	    function redraw() {
	        svg.attr("transform", "translate(" + d3.event.translate + ")" + " scale(" + d3.event.scale + ")");
	    }
	
	    // #############################   Function Area #######################
	    function wrap(text, width) {
	        text.each(function () {
	            var text = d3.select(this),
	                words = text.text().split(/\s+/).reverse(),
	                word,
	                line = [],
	                lineNumber = 0,
	                lineHeight = 1.1, // ems
	                x = text.attr("x"),
	                y = text.attr("y"),
	                dy = 0,
	                tspan = text.text(null)
	                    .append("tspan")
	                    .attr("x", x)
	                    .attr("y", y)
	                    .attr("dy", dy + "em");
	
	            while (word = words.pop()) {
	                line.push(word);
	                tspan.text(line.join(" "));
	
	                if (tspan.node().getComputedTextLength() > width) {
	                    line.pop();
	                    tspan.text(line.join(" "));
	                    line = [word];
	                    tspan = text.append("tspan")
	                        .attr("x", x)
	                        .attr("y", y)
	                        .attr("dy", ++lineNumber * lineHeight + dy + "em")
	                        .text(word);
	                }
	            }
	        });
	    }
	
	    function addPropertyRecursive(propertyName, propertyValueFunction, element) {
	        if (element[propertyName]) {
	            element[propertyName] = element[propertyName] + \' \' + propertyValueFunction(element);
	        } else {
	            element[propertyName] = propertyValueFunction(element);
	        }
	
	        if (element.children) {
	            element.children.forEach(function (v) {
	                addPropertyRecursive(propertyName, propertyValueFunction, v)
	            })
	        }
	
	        if (element._children) {
	            element._children.forEach(function (v) {
	                addPropertyRecursive(propertyName, propertyValueFunction, v)
	            })
	        }
	    }
	
	    function getEmployeesCount(node) {
	        var count = 1;
	
	        countChilds(node);
	
	        return count;
	
	        function countChilds(node) {
	            var childs = node.children ? node.children : node._children;
	
	            if (childs) {
	                childs.forEach(function (v) {
	                    count++;
	                    countChilds(v);
	                })
	            }
	        }
	    }
	
	    function expand(d) {
	        if (d.children) {
	            d.children.forEach(expand);
	        }
	
	        if (d._children) {
	            d.children = d._children;
	            d.children.forEach(expand);
	            d._children = null;
	        }
	
	        if (d.children) {
	            // if node has children and it\'s expanded, then  display -
	            setToggleSymbol(d, attrs.COLLAPSE_SYMBOL);
	        }
	    }
	
	    function collapse(d) {
	        if (d._children) {
	            d._children.forEach(collapse);
	        }
	
	        if (d.children) {
	            d._children = d.children;
	            d._children.forEach(collapse);
	            d.children = null;
	        }
	
	        if (d._children) {
	            // if node has children and it\'s collapsed, then  display +
	            setToggleSymbol(d, attrs.EXPAND_SYMBOL);
	        }
	    }
	
	    function setCollapsibleSymbolProperty(d) {
	        if (d._children) {
	            d.collapseText = attrs.EXPAND_SYMBOL;
	        } else if (d.children) {
	            d.collapseText = attrs.COLLAPSE_SYMBOL;
	        }
	    }
	
	    function setToggleSymbol(d, symbol) {
	        d.collapseText = symbol;
	        d3.select("*[data-id=\"" + d.uniqueIdentifier + "\"]").select("text").text(symbol);
	    }
	
	    /* expand current nodes collapsed parents */
	    function expandParents(d) {
	        while (d.parent) {
	            d = d.parent;
	
	            if (!d.children) {
	                d.children = d._children;
	                d._children = null;
	                setToggleSymbol(d, attrs.COLLAPSE_SYMBOL);
	            }
	        }
	    }
	
	    function show(selectors) {
	        display(selectors, "initial")
	    }
	
	    function hide(selectors) {
	        display(selectors, "none")
	    }
	
	    function display(selectors, displayProp) {
	        selectors.forEach(function (selector) {
	            var elements = getAll(selector);
	
	            elements.forEach(function (element) {
	                element.style.display = displayProp;
	            })
	        });
	    }
	
	    function set(selector, value) {
	        var elements = getAll(selector);
	
	        elements.forEach(function (element) {
	            element.innerHTML = value;
	            element.value = value;
	        })
	    }
	
	    function clear(selector) {
	        set(selector, "");
	    }
	
	    function get(selector) {
	        return document.querySelector(selector);
	    }
	
	    function getAll(selector) {
	        return document.querySelectorAll(selector);
	    }
	}';
}

/**
 * @param $plan
 *
 * @return string
 *
 * @since version
 */
function set_attr($plan): string
{
	$attr = 'status';

	foreach (plan_attr() as $k => $v)
	{
		if ($k === $plan && $v !== 'status')
		{
			$attr = $v;
		}
	}

	return $attr;
}

/**
 * @param $user_id
 *
 * @return mixed|null
 *
 * @since version
 */
function user_binary($user_id)
{
	$db = db();

	return $db->setQuery(
		'SELECT * ' .
		'FROM network_users u ' .
		'INNER JOIN network_binary b ' .
		'ON u.id = b.user_id ' .
		'WHERE user_id = ' . $db->quote($user_id)
	)->loadObject();
}