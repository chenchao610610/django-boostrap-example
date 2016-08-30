from django.http import HttpResponse
from django.shortcuts import render,render_to_response
from TestModel.models import Test

def hello(request):
	return HttpResponse("Hello world!!!!")

# Create your views here.
def index(req):
        ##
        ##
        #return HttpResponse("aa")
        return render_to_response('index.html',)

# Create your views here.
def home(req):
        ##
        ##
        #return HttpResponse("aa")
        return render_to_response('home.html',)


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
