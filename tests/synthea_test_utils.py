#---------------------------------------------------------------------------
# Copyright 2018 The Open Source Electronic Health Record Alliance
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
from selenium import webdriver
import argparse

def parse_args(description):
  parser = argparse.ArgumentParser(description=description)
  parser.add_argument("-r", dest='webroot', required=True,
    help="Web root of the Synthetic Patient Viewer instance to test.  eg. https://code.osehra.org/synthea/synthea_upload.php")
  parser.add_argument("-b", dest='browser', default="FireFox", required=False,
    help="Web browser to use for testing [FireFox, Chrome]")
  parser.add_argument('-local', default=False, action='store_true')
  return parser.parse_args()

def setup_webdriver(description):
  args = parse_args(description)
  result = vars(args)
  browser = result['browser'].upper()
  if result['browser'].upper() == "CHROME":
    driver = webdriver.Chrome()
  else:
    driver = webdriver.Firefox()
  webroot = result['webroot']
  driver.get(webroot)
  driver.maximize_window()
  is_local = result['local']
  return webroot, driver, browser.upper(), is_local
