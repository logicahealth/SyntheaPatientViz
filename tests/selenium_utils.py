#---------------------------------------------------------------------------
# Copyright 2015 The Open Source Electronic Health Record Alliance
#
# Licensed under the Apache License, Version 2.0 (the "License");
# you may not use this file except in compliance with the License.
# You may obtain a copy of the License at
#
#     http://www.apache.org/licenses/LICENSE-2.0
#
# Unless required by applicable law or agreed to in writing, software
# distributed under the License is distributed on an "AS IS" BASIS,
# WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
# See the License for the specific language governing permissions and
# limitations under the License.
#---------------------------------------------------------------------------

# Requires the PILLOW Python library
#
# Installation instructions are found here:
#    http://pillow.readthedocs.org/en/3.0.x/installation.html
#
from PIL import Image
from PIL import ImageChops
import os

  ############################
  # Reads in two PNG images and checks for the
  # images to be the same in a pixel by pixel check
  #
  # If more than 3000 pixels are different,
  # it writes out a B/W difference image
  ############################

# count_nonblack_pil function taken from:
# http://codereview.stackexchange.com/questions/55902/fastest-way-to-count-non-zero-pixels-using-python-and-pillow
def count_nonblack_pil(img):
    """Return the number of pixels in img that are not black.
    img must be a PIL.Image object in mode RGB.

    """
    bbox = img.getbbox()
    if not bbox: return 0
    return sum(img.crop(bbox)
               .point(lambda x: 255 if x else 0)
               .convert("L")
               .point(bool)
               .getdata())

def take_screenshot(driver, imageName, targetObj):
    loc = targetObj.location
    size = targetObj.size
    driver.save_screenshot("tmpImage.png")
    boundBox = (int(loc['x']),int(loc['y']),int(loc['x']+size['width']),int(loc['y']+size['height']))
    tmpImage = Image.open("tmpImage.png")
    tmpImage.crop(boundBox).save(imageName)

def compareImg(imageRoot):
  newFileName = os.path.normpath(os.getcwd() + "/" + imageRoot +'_new.png')
  compFileName = os.path.normpath(os.getcwd() + "/" + imageRoot +'_comp.gif')
  oldFileName = os.path.normpath(os.path.dirname(os.path.realpath(__file__))+'/imgData/'+imageRoot+'_old.png')

  new = Image.open(newFileName)
  old = Image.open(oldFileName)

  diff = ImageChops.difference(old,new)
  count = count_nonblack_pil(diff)
  if count >= 66000:
    # need to save as .gif to set transparency which is zeroed by difference.
    diff.save(compFileName,"GIF", transparency=255)
  # Output XML for Dart  Necessary for the upload of the image after
    print "<DartMeasurement name=\"ImageError\" type=\"numeric/double\">"+str(count)
    print "</DartMeasurement>"
    print "<DartMeasurement name=\"BaselineImage\" type=\"text/string\">Standard</DartMeasurement>"
    print "<DartMeasurementFile name=\"TestImage\" type=\"image/jpeg\">" + newFileName
    print"</DartMeasurementFile>"
    print"<DartMeasurementFile name=\"DifferenceImage\" type=\"image/jpeg\">" + compFileName
    print"</DartMeasurementFile>"
    print"<DartMeasurementFile name=\"ValidImage\" type=\"image/jpeg\"> " + oldFileName
    print"</DartMeasurementFile>"
    return False
  else:
    diff.save(compFileName,"GIF", transparency=255)
    return True
