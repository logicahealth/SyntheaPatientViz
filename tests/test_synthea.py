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
from selenium import webdriver
from selenium.webdriver.common.action_chains import ActionChains
from synthea_test_utils import setup_webdriver
import unittest
import re
import time

class test_synthea(unittest.TestCase):

  @classmethod
  def tearDownClass(cls):
    global driver
    driver.quit()

  def test_01_load_data(self):
    global driver
    driver.find_element_by_id("vivSelect").click()
    time.sleep(1)
    test = driver.find_element_by_id("vivSelect").find_elements_by_css_selector("option")
    test[3].click()
    time.sleep(10)
    self.assertTrue(driver.find_element_by_id("patInfoPlaceholder").text != "", "No Patient information loaded from JSON")
   
  def test_02_installDateRangeSelect(self):
    global driver
    # Check the changing of the start date
    startDateBox = driver.find_element_by_id('timeline_date_start')
    axisLabels = driver.find_elements_by_class_name("tick")
    origDate = axisLabels[0].find_element_by_tag_name('text').get_attribute("innerHTML")
    startDateBox.clear()
    startDateBox.send_keys("02/02/2000")
    driver.find_element_by_id('timeline_date_update').click()
    time.sleep(1)
    axisLabels = driver.find_elements_by_class_name("tick")
    endDate = axisLabels[0].find_element_by_tag_name('text').get_attribute("innerHTML")
    self.assertNotEqual(endDate, origDate, "Changing of the timeline_date_start did not alter the timeline")

    #Check the changing of the end date
    stopDateBox = driver.find_element_by_id('timeline_date_stop')
    axisLabels = driver.find_elements_by_class_name("tick")
    origDate = axisLabels[-1].find_element_by_tag_name('text').get_attribute("innerHTML")
    stopDateBox.clear()
    stopDateBox.send_keys("02/02/2014")
    driver.find_element_by_id('timeline_date_update').click()
    time.sleep(1)
    axisLabels = driver.find_elements_by_class_name("tick")
    endDate = axisLabels[-1].find_element_by_tag_name('text').get_attribute("innerHTML")
    self.assertNotEqual(endDate, origDate, "Changing of the timeline_date_stop did not alter the timeline")


  def test_03_installDateRangeReset(self):
    global driver
    axisLabels = driver.find_elements_by_class_name("tick")
    origStartDate = axisLabels[0].find_element_by_tag_name('text').get_attribute("innerHTML")
    origEndDate  = axisLabels[-1].find_element_by_tag_name('text').get_attribute("innerHTML")
    driver.find_element_by_id('timeline_date_reset').click()
    time.sleep(1)
    axisLabels = driver.find_elements_by_class_name("tick")
    newStartDate = axisLabels[0].find_element_by_tag_name('text').get_attribute("innerHTML")
    newEndDate = axisLabels[-1].find_element_by_tag_name('text').get_attribute("innerHTML")
    self.assertNotEqual(newStartDate, origStartDate, "Changes were not set by to the original via Reset")
    self.assertNotEqual(origEndDate ,newEndDate, "Changes were not set by to the original via Reset")

  def test_04_hoverNodes(self):
    oldText = driver.find_element_by_id("toolTip").text;
    lastObj = driver.find_elements_by_class_name('bar')[-1]
    ActionChains(driver).move_to_element(lastObj).perform()
    time.sleep(1)
    self.assertNotEqual(driver.find_element_by_id("toolTip").text, oldText, "No new information found in modal window after clicking on node")
  def test_05_clickNodes(self):
    global driver
    oldText = driver.find_element_by_id("filteredObjs").text;
    lastObj = driver.find_elements_by_class_name('bar')[-1]
    ActionChains(driver).move_to_element(lastObj).click().perform()
    time.sleep(1)
    self.assertNotEqual(driver.find_element_by_id("filteredObjs").text, oldText, "No new information found in modal window after clicking on node")
    driver.find_element_by_class_name("ui-dialog-titlebar-close").click()
  def test_06_summaryCheck(self):
    global driver
    oldText = driver.find_element_by_id("filteredObjs").text;
    lastObj = driver.find_elements_by_class_name('bar')[-1]
    ActionChains(driver).move_to_element_with_offset(lastObj, -10, -10).context_click().click().perform()
    time.sleep(1)
    self.assertNotEqual(driver.find_element_by_id("filteredObjs").text, oldText, "No new information found in modal window after clicking on summary bar")
    driver.find_element_by_class_name("ui-dialog-titlebar-close").click()
  def test_07_filterCheck(self):
    global driver
    totalNum = len(driver.find_elements_by_class_name('bar'))
    legends = driver.find_element_by_id("legend_placeholder").find_elements_by_css_selector("rect")
    for legendBox in legends:
      legendBox.click()
      time.sleep(1)
      self.assertTrue(len(driver.find_elements_by_class_name('bar')) < totalNum, "Clicking on %s didn't remove objects from display" % legendBox.text)
      driver.find_element_by_class_name("active").click()
      time.sleep(1)
    
  

if __name__ == '__main__':
  description = "Testing the single page that is the Synthea Upload Visualization"
  webroot, driver, browser, is_local = setup_webdriver(description)
  suite = unittest.TestLoader().loadTestsFromTestCase(test_synthea)
  unittest.TextTestRunner(verbosity=2).run(suite)
