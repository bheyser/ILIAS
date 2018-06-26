/**
 * Created with JetBrains PhpStorm.
 * User: tjoussen
 * Date: 04.02.13
 * Time: 12:12
 * To change this template use File | Settings | File Templates.
 */

/**
 * Holds the Backgroundimage in the Canvascontainer
 *
 * @type {fabric.Image}
 */
var background = null;

/**
 * A list Containing all Elements in the Canvas-Container
 *
 * @type {Array}
 */
var list = [];

/**
 * The color of the elements
 *
 * @type string
 */
var color;

/**
 * Initialize a Fabric Canvas-Container in a Nativ HTML5 Canvas Container
 * @param String name
 */
function initCanvas(id, color){
    var name = "container";
    if(id != "container"){
        name = "container" + id;
    }

    var container = new fabric.Canvas(name, {
        selection: false
    });
    container.id = id;
    container.on({
        'object:moving': onObjectMoving,
        'object:selected': onSelected,
        'object:modified': onObjectModified
    });

    this.color = "04427e";
    if(color != undefined && color != ""){
        this.color = color;
    }

    return container;
}

/**
 * Initialize the Background for the Canvas-Container
 *
 * @param String src
 */
function initBackground(src, anchor){
    if(anchor == undefined){
        anchor = '#canvas_wrapper';
    }

    $(anchor).css(
        'background', 'url('+src+') center center no-repeat'
    );
}

/**
 * Adds a new Element to the Canvas-Container
 *
 * @param int index
 */
function addElement(index, container){
    drawElementObject(index, container);
    drawAnchorObject(index, container);
    drawConnectionObject(index, container);
    addToContainer(index, container);
}

function addInput(container, read_only){
    var regex = /^answer\[(\d+)\]/;
    var index = regex.exec($(this).attr('name'));
    drawElementObject(index[1], container);
    drawAnchorObject(index[1], container);
    drawConnectionObject(index[1]);

    $(this).css('position', 'absolute')
        .css('left',parseInt(list[index[1]].element.left) - parseInt($(this).css('width')) / 2 + 'px')
        .css('top', parseInt(list[index[1]].element.top) - parseInt($(this).css('height')) / 2 + 'px');

    if(read_only){
        $(this).attr('disabled', 'disabled');
    }

    container.add(list[index[1]].anchor);
    container.add(list[index[1]].connection);
}

/**
 * Draws the Elementanchor representing one answer in the assGraphicalAssignmentQuestion
 *
 * @param int index
 */
function drawElementObject(index, container){
    var rect = new fabric.Rect({
        fill:'#' + this.color,
        width: 100,
        height: 30
    });

    var key = parseInt(index) + 1;
    var text = new fabric.Text("Element "+ key, {
        fontSize: 20
    });

    var left = 0;
    var top = 0;
    if($('#input_wrapper'+container.id).length > 0){
        left = $('#input_wrapper'+container.id).find('input[name="answer['+ index +'][destination_x]"]').val();
        top = $('#input_wrapper'+container.id).find('input[name="answer['+ index +'][destination_y]"]').val();
    }else{
        left = $('input[name="answer['+ index +'][destination_x]"]').val();
        top = $('input[name="answer['+ index +'][destination_y]"]').val();
    }

    if(left == 0 || left == ""){
        left = 100 + (list.length * 10);
    }
    if(top == 0 || top == ""){
        top = container.height - 50 - (list.length * 10);
    }

    var group = new fabric.Group([rect, text ], {
        left: left,
        top: top,
        hasControls: false
    });
    group.name = "element";
    group.index = index;

    addToList(index, {element: group});
}

/**
 * Draw the Anchor to set the connection between the Answerelement and a position in the Image
 *
 * @param int index
 */
function drawAnchorObject(index, container){
    var element = list[index].element;

    var left = 0;
    var top = 0;
    if($('#input_wrapper'+container.id).length > 0){
        left = $('#input_wrapper'+container.id).find('input[name="answer['+ index +'][target_x]"]').val();
        top = $('#input_wrapper'+container.id).find('input[name="answer['+ index +'][target_y]"]').val();
    }else{
        left = $('input[name="answer['+ index +'][target_x]"]').val();
        top = $('input[name="answer['+ index +'][target_y]"]').val();
    }

    if(left == 0){
        left= parseInt(element.left) + 60;
    }
    if(top == 0){
        top= parseInt(element.top) - 80;
    }

    var triangle = new fabric.Triangle({
        width: 20, height: 30, fill: '#' + this.color, left: left, top: top
    });
	triangle.hasBorders = false;
	triangle.hasControls = false;
    triangle.name = 'anchor';
    triangle.index = index;

    addToList(index, {anchor: triangle});
}

/**
 * Draws the Connection between the Anchor and the Answerelement
 *
 * @param int index
 */
function drawConnectionObject(index){
    var line = new fabric.Line(
        [parseInt(list[index].element.left) , parseInt(list[index].element.top) , parseInt(list[index].anchor.left) ,parseInt(list[index].anchor.top)],
        {
            fill: '#' + this.color,
            strokeWidth: 3
        }
    );
    line.selectable = false;
    line.name= "connection";
    line.index = index;

    var anchor = list[index].anchor;
    var angle = Math.atan2(line.x1 - line.x2, line.y1 - line.y2) * -1;
    angle = (360 / (2 * Math.PI)) * angle;
    anchor.set('angle', angle);

    addToList(index, {connection: line});
}

function addToContainer(index, container){
    container.add(list[index].element);
    container.add(list[index].anchor);
    container.add(list[index].connection);

    list[index].element.bringToFront();
    list[index].anchor.bringToFront();
}

/**
 * Adds a new Element to the List. It is possible to add single Elements for one Index
 *
 * @param int index
 * @param Array config
 */
function addToList(index, config){
    if(list[index] == null){
        list[index] = {
            anchor: null,
            element: null,
            connection: null
        }
    }
    if(config.anchor != undefined){
        list[index].anchor = config.anchor;
    }
    if(config.element != undefined){
        list[index].element = config.element;
    }
    if(config.connection != undefined){
        list[index].connection = config.connection;
    }
}

/**
 * Callback for the object:move from fabric
 *
 * @param e
 */
function onObjectMoving(e){
    var activeObject = e.target;

    if(activeObject.left < activeObject.width / 2){
        activeObject.left = activeObject.width / 2;
    }
    if(activeObject.left > e.target.canvas.width - (activeObject.width / 2)){
        activeObject.left = (e.target.canvas.width - (activeObject.width / 2));
    }
    if(activeObject.top < activeObject.height / 2){
        activeObject.top = activeObject.height / 2;
    }
    if(activeObject.top > e.target.canvas.height - (activeObject.height / 2)){
        activeObject.top = (e.target.canvas.height - (activeObject.height / 2));
    }

    if(activeObject.name == 'anchor'){
        var connection = list[activeObject.index].connection;
        connection.set({ 'x2': activeObject.left, 'y2': activeObject.top });

        var angle = Math.atan2(connection.x1 - connection.x2, connection.y1 - connection.y2) * -1;
        angle = (360 / (2 * Math.PI)) * angle;
        activeObject.set('angle', angle);

    }else if (activeObject.name == 'element'){
        var connection = list[activeObject.index].connection;
        connection.set({ 'x1': activeObject.left, 'y1': activeObject.top });

        var angle = Math.atan2(connection.x1 - connection.x2, connection.y1 - connection.y2) * -1;
        angle = (360 / (2 * Math.PI)) * angle;
        list[activeObject.index].anchor.set('angle', angle);
    }
}

/**
 * Callback for object:selected from fabric
 *
 * @param e
 */
function onSelected(e){
    if(e.target.name != 'background'){
        e.target.bringToFront();
    }
}

function onObjectModified(e){
    if(e.target.name == 'element'){
        $('input[name="answer['+ e.target.index +'][destination_x]"]').val(e.target.left);
        $('input[name="answer['+ e.target.index +'][destination_y]"]').val(e.target.top);
    }else if(e.target.name == 'anchor'){
        $('input[name="answer['+ e.target.index +'][target_x]"]').val(e.target.left);
        $('input[name="answer['+ e.target.index +'][target_y]"]').val(e.target.top);
    }
}

fabric.Canvas.prototype.getAbsoluteCoords = function(object) {
    return {
        left: parseInt(object.left)/* + parseInt(this._offset.left)*/,
        top: parseInt(object.top)/* + parseInt(this._offset.top)*/
    };
}


