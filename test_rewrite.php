<?php
if (in_array('mod_rewrite', apache_get_modules())) {
  echo "Rewrite module is ENABLED";
} else {
  echo "Rewrite module is DISABLED";
}
