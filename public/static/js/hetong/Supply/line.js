var mydate = new Date();
var ads = [];
$(function(){
    var mot = $('.datepickerMonth a span').text().split(',')[0];
    switch(mot){
        case '一月':
        mot = 1;
        break;
        case '二月':
         mot = 2;
        break;
        case '三月':
         mot = 3;
        break;
        case '四月':
         mot = 4;
        break;
        case '五月':
         mot = 5;
        break;
        case '六月':
         mot = 6;
        break;
        case '七月':
         mot = 7;
        break;
        case '八月':
         mot = 8;
        break;
        case '九月':
         mot = 9;
        break;
        case '十月':
         mot = 10;
        break;
        case '十一月':
         mot = 11;
        break;
        case '十二月':
         mot = 12;
        break;
    }
    $('.datepickerDays tr td').each(function(i){
        if($(this).find('span').text()==mydate.getDate())
        {

            //$(this).css('border','1px solid red');
            ads.push($(this));
            return;
        }
    })
    if(mot==mydate.getMonth()+1&&mydate.getDate()<20)
    {
        ads[0].css('border','1px solid red');
    }else{
        ads[1].css('border','1px solid red');
    }
    console.log(ads);
    


})
function myinitTime(ms)
{
	var mot = mydate.getMonth()+1;
    $('#datepicker').DatePicker({
        flat: true,
        date: [],
        current: mydate,
        calendars: 1,
        mode: ms,
        starts: 1
    });
}
myinitTime('multiple');
$('.times').click(function(){
    $('#datepicker').html('');
    myinitTime('range');
    $('.days').removeClass('active');
    $(this).addClass('active');
    $('#timehidInput').val('1');
})
$('.days').click(function(){
    $('#datepicker').html('');
    myinitTime('multiple');
    $('.times').removeClass('active');
    $(this).addClass('active');
    $('#timehidInput').val('0');
})

function shows(){
    var mytimes;
    if( $('#timehidInput').val()==0)
    {
        mytimes = $('#datepicker').DatePickerGetDate('Y-m-d');
    }else{
        var timesArr = [];
        mytimes = [];
        timesArr = $('#datepicker').DatePickerGetDate('Y-m-d');
        var first = new Date(timesArr[0]).getTime();
        var last = new Date(timesArr[1]).getTime();
        var next = first;
        while (next <= last) {
            mytimes.push(date('Y-m-d',Date.parse((new Date(next)).toUTCString())/1000))
            next += 24 * 3600 * 1000;
        }
    }
    return mytimes
}



function date(format, timestamp){   
        var a, jsdate=((timestamp) ? new Date(timestamp*1000) : new Date());  
        var pad = function(n, c){  
            if((n = n + "").length < c){  
                return new Array(++c - n.length).join("0") + n;  
            } else {  
                return n;  
            }  
        };  
        var txt_weekdays = ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];  
        var txt_ordin = {1:"st", 2:"nd", 3:"rd", 21:"st", 22:"nd", 23:"rd", 31:"st"};  
        var txt_months = ["", "January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];  
    var f = {  
            d: function(){return pad(f.j(), 2)},  
            D: function(){return f.l().substr(0,3)},  
            j: function(){return jsdate.getDate()},  
            l: function(){return txt_weekdays[f.w()]},  
            N: function(){return f.w() + 1},  
            S: function(){return txt_ordin[f.j()] ? txt_ordin[f.j()] : 'th'},  
            w: function(){return jsdate.getDay()},  
            z: function(){return (jsdate - new Date(jsdate.getFullYear() + "/1/1")) / 864e5 >> 0},  
            W: function(){  
                var a = f.z(), b = 364 + f.L() - a;  
                var nd2, nd = (new Date(jsdate.getFullYear() + "/1/1").getDay() || 7) - 1;  
                if(b <= 2 && ((jsdate.getDay() || 7) - 1) <= 2 - b){  
                    return 1;  
                } else{  
                    if(a <= 2 && nd >= 4 && a >= (6 - nd)){  
                        nd2 = new Date(jsdate.getFullYear() - 1 + "/12/31");  
                        return date("W", Math.round(nd2.getTime()/1000));  
                    } else{  
                        return (1 + (nd <= 3 ? ((a + nd) / 7) : (a - (7 - nd)) / 7) >> 0);  
                    }  
                }  
            },  
            F: function(){return txt_months[f.n()]},  
            m: function(){return pad(f.n(), 2)},  
            M: function(){return f.F().substr(0,3)},  
            n: function(){return jsdate.getMonth() + 1},  
            t: function(){  
                var n;  
                if( (n = jsdate.getMonth() + 1) == 2 ){  
                    return 28 + f.L();  
                } else{  
                    if( n & 1 && n < 8 || !(n & 1) && n > 7 ){  
                        return 31;  
                    } else{  
                        return 30;  
                    }  
                }  
            },  
            L: function(){var y = f.Y();return (!(y & 3) && (y % 1e2 || !(y % 4e2))) ? 1 : 0},  
            Y: function(){return jsdate.getFullYear()},  
            y: function(){return (jsdate.getFullYear() + "").slice(2)},  
            a: function(){return jsdate.getHours() > 11 ? "pm" : "am"},  
            A: function(){return f.a().toUpperCase()},  
            B: function(){  
                var off = (jsdate.getTimezoneOffset() + 60)*60;  
                var theSeconds = (jsdate.getHours() * 3600) + (jsdate.getMinutes() * 60) + jsdate.getSeconds() + off;  
                var beat = Math.floor(theSeconds/86.4);  
                if (beat > 1000) beat -= 1000;  
                if (beat < 0) beat += 1000;  
                if ((String(beat)).length == 1) beat = "00"+beat;  
                if ((String(beat)).length == 2) beat = "0"+beat;  
                return beat;  
            },  
            g: function(){return jsdate.getHours() % 12 || 12},  
            G: function(){return jsdate.getHours()},  
            h: function(){return pad(f.g(), 2)},  
            H: function(){return pad(jsdate.getHours(), 2)},  
            i: function(){return pad(jsdate.getMinutes(), 2)},  
            s: function(){return pad(jsdate.getSeconds(), 2)},  
            O: function(){  
                var t = pad(Math.abs(jsdate.getTimezoneOffset()/60*100), 4);  
                if (jsdate.getTimezoneOffset() > 0) t = "-" + t; else t = "+" + t;  
                return t;  
            },  
            P: function(){var O = f.O();return (O.substr(0, 3) + ":" + O.substr(3, 2))},  
            c: function(){return f.Y() + "-" + f.m() + "-" + f.d() + "T" + f.h() + ":" + f.i() + ":" + f.s() + f.P()},  
            U: function(){return Math.round(jsdate.getTime()/1000)}  
        };  
              
        return format.replace(/[\\]?([a-zA-Z])/g, function(t, s){  
            if( t!=s ){  
                // escaped  
                ret = s;  
            } else if( f[s] ){  
                // a date function exists  
                ret = f[s]();  
            } else{  
                // nothing special  
                ret = s;  
            }  
            return ret;  
        });  
    }  