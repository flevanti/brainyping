<?php
interface monitor_interface {
      function getAverage();
      function getWorst();
      function getBest();
      function getFailed();
      function getSuccess();
      function getRequests();
      function getResultCode();
      function getResultPerc();
      function getIP();
      function getDetails();
      function getDetailsAsString();
      function getLastError();
      function getLastErrorCode();

}