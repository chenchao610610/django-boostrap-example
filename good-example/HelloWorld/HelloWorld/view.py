#coding=utf-8  
from django.http import HttpResponse
from django.http import HttpRequest
from django.http import HttpResponseRedirect
from django.shortcuts import render,render_to_response
from TestModel.models import Test
from django.conf import settings
import subprocess
from django.views.decorators.csrf import csrf_exempt

def test(request):
	return HttpResponse(settings.BBB)

def hello(request):
	return HttpResponse("Hello world!!!!")

def movie(request):
    imdbid  = request.GET.get('imdbid')
    title   = request.GET.get('title')
    url     = request.GET.get('url')
    is_full = request.GET.get('is_full')
    is_imdb = request.GET.get('is_imdb')
    is_trail= request.GET.get('is_trail')
    url = url + "?imdbid="  + imdbid

    msg = ""
    php_path = "/home/webserver/local/php-5.3.18/bin/php"
    script_path = "/home/webserver/HelloWorld/script/"
    if is_full == 'true':
        path = script_path + "get_full_movie.php"
        cmd = php_path + " " + path + " " + url
        proc=subprocess.Popen([cmd],shell=True,stdout=subprocess.PIPE)
        response=proc.stdout.read()
        msg = "get full movie ok...\n"

    if is_imdb == 'true':
        path = script_path + "get_one_imdb.php"
        cmd = php_path + " " + path + " " + imdbid
        proc=subprocess.Popen([cmd],shell=True,stdout=subprocess.PIPE)
        response=proc.stdout.read()

        path =  script_path + "upload_img.php"
        cmd = php_path + " " + path + " " + imdbid
        proc=subprocess.Popen([cmd],shell=True,stdout=subprocess.PIPE)
        response=proc.stdout.read()
        msg = msg + "get imdbinfo ok...\n"

    if is_trail == 'true':
        path = script_path + "get_youtube_trailer.php";       
        cmd = php_path + " " + path + " " + imdbid
        proc=subprocess.Popen([cmd],shell=True,stdout=subprocess.PIPE)
        response=proc.stdout.read()
        msg = msg + "get youtube trailer ok...\n"
       
    return HttpResponse(msg)


# Create your views here.
@csrf_exempt
def index(req):
        ##
        ##
        #return HttpResponse("aa")
        username = req.POST.get('username')
        password = req.POST.get('password')

        if username == 'admin' and password == 'admin':
            return render_to_response('index.html',)
        else:
            #return render_to_response('login.html',)
            msg = "你输入的密码和账户名不匹配，是否忘记密码或忘记会员名"
            url = "http://cc.eletube.mobi/login/?msg=" + msg
            return HttpResponseRedirect(url)



def stat(req):
        ##
        ##
        #return HttpResponse("aa")
        path = req.get_full_path
        if "type=pic" in str(path):
           #data = {'current_date':'2019:09:099', 'data1':900,'data2':914,'data3':'4054','test':req.get_full_path}
           data = {'current_date':'2019:09:099', 'data1':900,'data2':914,'data3':'4054','test':'aaaaa'}
           return render_to_response('stat.html', data)
        else:
           return render_to_response('stat1.html')
        #if 'type=pic' in path:
        #   data = {'current_date':'2019:09:099', 'data1':900,'data2':914,'data3':'4054','test':req.get_full_path}
        #   return render_to_response('stat.html', data)
        #else:
        #   return render_to_response('index.html')

# Create your views here.
def home(req):
        ##
        ##
        #return HttpResponse("aa")
        return render_to_response('home.html',)

def login(req):
        return render_to_response('login.html',)

def signup(req):
        return render_to_response('signup.html',)

def faq(req):
        return render_to_response('faq.html',)

# database operate
def insertData(request):
	test1 = Test(name='w3cschool.cc')
	test1.save()
	return HttpResponse("<p>insert ok!!!</p>")


# database operate
def getData(request):
	# init
	response = ""
	response1 = ""
	
	# all()
	list = Test.objects.all()
		
	# filter where
	response2 = Test.objects.filter(id=2) 
	
	# get one object
	response3 = Test.objects.get(id=2) 
	
	# limit 0,2
	Test.objects.order_by('name')[0:2]
	
	#data order
	Test.objects.order_by("id")
	
	# filter, order by 
	Test.objects.filter(name="w3cschool.cc").order_by("id")
	
	# input data
	for var in list:
		response1 += var.name + " "
	response = response1
	return HttpResponse("<p>" + response + "</p>")

# delete data
def deleteData(request):
	# delete id = 1
	test1 = Test.objects.get(id=5)
	test1.delete()
	
	# other method
	# Test.objects.filter(id=1).delete()
	
	# delete all data
	# Test.objects.all().delete()
	
	return HttpResponse("<p>delete ok!!!</p>")


