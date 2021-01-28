<?php

namespace OCA\JMAP\JMAP\Contact;

use JsonSerializable;

class Contact implements JsonSerializable
{
    /** @var string */
    private $id;

    /** @var string */
    private $firstName;

    /** @var string */
    private $lastName;

    private $prefix;

    private $suffix;

    private $nickname;

    private $birthday;

    private $anniversary;

    private $jobTitle;

    private $company;

    private $department;

    private $notes;

    /** @var ContactInformation[] */
    private $emails;

    /** @var ContactInformation[] */
    private $phones;

    /** @var ContactInformation[] */
    private $online;

    /** @var Address[] */
    private $addresses;

    private $uid;

    public function getId()
    {
        return $this->id;
    }

    public function setId($id) {
	    $this->id = $id;
    }

    public function getFirstName()
    {
        return $this->firstName;
    }

    public function setFirstName($firstName) {
	    $this->firstName = $firstName;
    }

    public function getLastName()
    {
        return $this->lastName;
    }

    public function setLastName($lastName) {
	    $this->lastName = $lastName;
    }

    public function getPrefix() {
	    return $this->prefix;
    }

    public function setPrefix($prefix) {
	    $this->prefix = $prefix;
    }

    public function getSuffix() {
        return $this->suffix;
    }

    public function setSuffix($suffix) {
        $this->suffix = $suffix;
    }

    public function getNickname() {
        return $this->nickname;
    }

    public function setNickname($nickname) {
        $this->nickname = $nickname;
    }

    public function getBirthday() {
        return $this->birthday;
    }

    public function setBirthday($birthday) {
        $this->birthday = $birthday;
    }

    public function getAnniversary() {
        return $this->anniversary;
    }

    public function setAnniversary($anniversary) {
        $this->anniversary = $anniversary;
    }

    public function getJobTitle() {
        return $this->jobTitle;
    }

    public function setJobTitle($jobTitle) {
        $this->jobTitle = $jobTitle;
    }

    public function getCompany() {
        return $this->company;
    }

    public function setCompany($company) {
        $this->company = $company;
    }

    public function getDepartment() {
        return $this->department;
    }

    public function setDepartment($department) {
        $this->department = $department;
    }

    public function getNotes() {
        return $this->notes;
    }

    public function setNotes($notes) {
        $this->notes = $notes;
    }

    public function getEmails()
    {
        return $this->emails;
    }

    public function setEmails($emails)
    {
        $this->emails =$emails;
    }

    public function getPhones()
    {
        return $this->phones;
    }

    public function setPhones($phones)
    {
        $this->phones = $phones;
    }

    public function getOnline()
    {
        return $this->online;
    }

    public function setOnline($online)
    {
        $this->online = $online;
    }

    public function getAddresses()
    {
        return $this->addresses;
    }

    public function setAddresses($addresses)
    {
        $this->addresses = $addresses;
    }

    public function getUid()
    {
        return $this->uid;
    }

    public function setUid($uid) {
	    $this->uid = $uid;
    }

    public function jsonSerialize()
    {
        return (object)[
            "id" => $this->getId(),
            "firstName" => $this->getFirstName(),
            "lastName" => $this->getLastName(),
            "prefix" => $this->getPrefix(),
            "suffix" => $this->getSuffix(),
            "nickname" => $this->getNickname(),
            "birthday" => $this->getBirthday(),
            "anniversary" => $this->getAnniversary(),
            "jobTitle" => $this->getJobTitle(),
            "company" => $this->getCompany(),
            "department" => $this->getDepartment(),
            "notes" => $this->getNotes(),
            "emails" => $this->getEmails(),
            "phones" => $this->getPhones(),
            "online" => $this->getOnline(),
            "addresses" => $this->getAddresses(),
            "uid" => $this->getUid()
        ];
    }
}
