def get_data():
    aaa={'aa':'bb','cc':'dd'}
    file_object = open('thefile.txt', 'a')
    try:
      all_the_text = file_object.write("first")
    finally:
     file_object.close( )
    return aaa

