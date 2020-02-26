// 0/1 = start/end
// 2 = state
// 3 = length, ms
// 4 = timer
// 5 = epoch
// 6 = disp el

var t=[0, 0, 0, 0, 0, 0];

function ss()
{
	t[t[2]]=(new Date()).valueOf();
	t[2]=1-t[2];

	if (0==t[2])
	{
		clearInterval(t[4]);
		t[3]+=t[1]-t[0];
		t[4]=t[1]=t[0]=0;
		disp();
	}
	else
	{
		t[4]=setInterval(disp, 43);
	}
}

function r()
{
	if (t[2]) ss();
	t[4]=t[3]=t[2]=t[1]=t[0]=0;
	t[5]=new Date(1970, 1, 1, 0, 0, 0, 0).valueOf();
	t[6].value='00:00:00';
}

function force_stop()
{
	t[t[2]]=(new Date()).valueOf();
	t[2]=1-t[2];

	if (0==t[2])
	{
		clearInterval(t[4]);
		t[3]+=t[1]-t[0];
		t[4]=t[1]=t[0]=0;
		disp();
	}
}

function timer_running() {
	return 0!=t[2];
}

function disp()
{
	if (t[2]) t[1]=(new Date()).valueOf();
	t[6].value=format(t[3]+t[1]-t[0]);
}

function format(ms)
{
	var d=new Date(ms+t[5]).toString().replace(/.*([0-9][0-9]:[0-9][0-9]:[0-9][0-9]).*/, '$1');
	return d;
}

function load_timer(display_element, h, m, s)
{
	t[5]=new Date(1970, 1, 1, h, m, s, 0).valueOf();
	t[6]=document.getElementById(display_element);
	disp();
}
