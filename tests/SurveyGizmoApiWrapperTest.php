<?php

namespace spacenate\Test;

use spacenate\SurveyGizmoApiWrapper;

class SurveyGizmoApiWrapperTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructorCanBeCalledWithOrWithoutCredentials()
    {
        $sg = new SurveyGizmoApiWrapper("test@case.com", "plaintext_password", "pass");

        $result = $sg->getCredentials();
        $this->assertEquals("user:pass=test@case.com:plaintext_password", $result);
    }

    public function testConstructorCanBeCalledWithOrWithoutCredentials2()
    {
        $sg = new SurveyGizmoApiWrapper();

        $result = $sg->getCredentials();
        $this->assertEquals(false, $result);
    }

    /**
     * @dataProvider credentialsTypeProvider
     * @testdox When setting credentials, type may be md5, pass, or oauth
     */
    public function testTypeMayBeSpecifiedWhenSettingCredentials($type, $expected)
    {
        $sg = new SurveyGizmoApiWrapper();

        $result = $sg->setCredentials("test@case.com", "secret", $type);
        $this->assertEquals($expected, $result);
    }

    public function credentialsTypeProvider()
    {
        return array(
            array("md5", true),
            array("pass", true),
            array("oauth", true),
            array("foobar", false),
            array(1, false),
            array(false, false)
        );
    }

    public function testCredentialTypeDefaultsToPass()
    {
        $sg = new SurveyGizmoApiWrapper();

        $sg->setCredentials("test@case.com", "secret");

        $result = $sg->getCredentials();
        $this->assertEquals("api_token=test@case.com&api_token_secret=secret", $result);
    }

    /**
     * @dataProvider formatProvider
     * @testdox Return format can be set to either json, pson, xml, or debug
     */
    public function testReturnFormatMayBeSpecified($format, $expected)
    {
        $sg = new SurveyGizmoApiWrapper();

        $result = $sg->setOutputFormat($format);
        $this->assertEquals($expected, $result);
    }

    public function formatProvider()
    {
        return array(
            array("json", true),
            array("pson", true),
            array("xml", true),
            array("debug", true),
            array("foobar", false),
            array(1, false),
            array(false, false)
        );
    }

    public function testFilterStringsCanBeCreatedFromSimpleArrays()
    {
        $sg = new SurveyGizmoApiWrapper();

        $filterArray = array("createdon", ">", "2015-05-15+12:00:00");

        $result = $sg->getFilterString($filterArray);
        $this->assertEquals("filter[field][0]=createdon&filter[operator][0]=>&filter[value][0]=2015-05-15+12:00:00", $result);
    }

    public function testFilterStringsCanBeCreatedFromMultidimensionalArrays()
    {
        $sg = new SurveyGizmoApiWrapper();

        $filterArray = array(
            array("createdon", ">", "2015-05-15+12:00:00"),
            array("status", "=", "Launched")
        );

        $result = $sg->getFilterString($filterArray);
        $this->assertEquals("filter[field][0]=createdon&filter[operator][0]=>&filter[value][0]=2015-05-15+12:00:00&filter[field][1]=status&filter[operator][1]==&filter[value][1]=Launched", $result);
    }

    public function testGetValidParametersByKey()
    {
        $sg = new SurveyGizmoApiWrapper();

        $params = array(
            "meow" => "meow meow meow",
            "woof" => "woof woof woof"
        );
        $allowed = array("meow", "woof");

        $result = $sg->getValidParameters($params, $allowed);
        $this->assertEquals($params, $result);
    }

    public function testFilterOutInvalidParametersByKey()
    {
        $sg = new SurveyGizmoApiWrapper();

        $params = array(
            "meow" => "meow meow meow",
            "woof" => "woof woof woof",
            "hiss" => "ssss sssss sss",
            false => "nope",
            2 => 9000
        );
        $allowed = array("meow", "woof");

        $result = $sg->getValidParameters($params, $allowed);
        $this->assertEquals(array("meow" => "meow meow meow", "woof" => "woof woof woof"), $result);
    }

    public function testGetValidParametersByRegxp()
    {
        $sg = new SurveyGizmoApiWrapper();

        $params = array(
            "meow" => "meow meow meow",
            "meeeooww" => "meowwwww meowwwwwww meeowww",
            "MEEOW" => "MEOW. MEOW. MEOW."
        );
        $allowed_regxp = array("/^\S+$/i");

        $result = $sg->getValidParameters($params, array(), $allowed_regxp);
        $this->assertEquals($params, $result);
    }

    public function testFilterOutInvalidParametersByRegxp()
    {
        $sg = new SurveyGizmoApiWrapper();

        $params = array(
            "meow" => "meow",
            "meeeooww" => "meowwwww",
            "woof" => "woof woof woof!",
            "moo" => "mmoooooooo"
        );
        $allowed_regxp = array("/^m+e+o+w+$/i");

        $result = $sg->getValidParameters($params, array(), $allowed_regxp);
        $this->assertEquals(array("meow" => "meow", "meeeooww" => "meowwwww"), $result);
    }

    public function testTestUriCreatedCorrectly()
    {

        $httpMock = $this->getMockBuilder('Http\HttpClientInterface')
                         ->setMethods(array('sendRequest', 'getStatusCode', 'getResponseBody'))
                         ->getMock();
        $httpMock->expects($this->once())
                 ->method('sendRequest')
                 ->with($this->equalTo('https://restapi.surveygizmo.com/head/survey.json?_method=GET&user:pass=bob@bobmail.bob:poop&page=1&resultsperpage=0'));

        $sg = new SurveyGizmoApiWrapper("bob@bobmail.bob", "poop", "pass", array("httpClient" => $httpMock));
        $sg->testCredentials();
    }

}