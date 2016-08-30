from django.conf.urls import patterns, include, url
from django.contrib.staticfiles.urls import staticfiles_urlpatterns

from django.contrib import admin
from HelloWorld.view import hello
from HelloWorld.view import insertData
from HelloWorld.view import getData
from HelloWorld.view import deleteData
from HelloWorld.view import index
from HelloWorld.view import home
admin.autodiscover()


urlpatterns = patterns('',
    # Examples:
    # url(r'^$', 'HelloWorld.views.home', name='home'),
    # url(r'^blog/', include('blog.urls')),
    #('^hello/$', hello),
    url(r'^index/$', index),
    url(r'^home/$', home),
    url(r'^hello/',   'HelloWorld.view.hello', name='hello'),
    url(r'^insert/$', insertData),
    url(r'^get/$', getData),
    url(r'^delete/$', deleteData),
    url(r'^admin/', include(admin.site.urls)),
)

urlpatterns += staticfiles_urlpatterns()
