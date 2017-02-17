# Manual Testplan 

This document describes the tests that need to be made manually at the end of the migration, with all automated
tests having run successfully.

## Test Plan

### POST

In general, all areas that involve a $_POST request need to be manually verified as we are currently only
testing GET requests automatically. Examples for this are the Powermail Forms (ideally search the database for
all pages that contain a powermail plugin to make sure we test them all), the job application forms, kea Form, ...
