<?php
class Plugin_email extends Plugin {

  public $meta = array(
    'name'       => 'Email',
    'version'    => '1',
    'author'     => 'Eric Barnes',
    'author_url' => 'http://ericlbarnes.com'
  );

  protected $validation = array();

  /**
   * Email Form
   *
   * Allows you to create an email form and parses the posted data.
   */
  public function form() {
    $options['to'] = $this->fetch_param('to');
    $options['cc'] = $this->fetch_param('cc', '');
    $options['bcc'] = $this->fetch_param('bcc', '');
    $options['from'] = $this->fetch_param('from', '');
    $options['subject'] = $this->fetch_param('subject', 'Email Form');

    $required = $this->fetch_param('required');
    $output = '';
    $vars = array(array());

    // Handle the posted data
    if (isset($_POST) and ! empty($_POST)) {
      if ( ! $this->validate($_POST, $required)) {
        $vars = array(
          array('error' => true, 'errors' => $this->validation)
        );
      } else {
        if ($this->send($_POST, $options)) {
          $vars = array(array('success' => true));
        } else {
          $vars = array(array('error' => true,
            'errors' => array('error' => 'Could not send email'))
          );
        }
      }
    }

    $output .= '<form method="post">';
    $output .= $this->parse_loop($this->content, $vars);
    $output .= '</form>';

    return $output;
  }

  /**
   * Validate the submitted form data
   *
   * @param array input
   * @param string required
   * @return bool
   */
  protected function validate($input, $required) {
    $required = explode('|', str_replace('from', '', $required));

    // From is always required
    if ( ! isset($input['from']) or $input['from'] == '') {
      $this->validation[0]['error'] = 'From is required';
    }

    foreach ($required as $key => $value) {
      if ($value != '' and $input[$value] == '') {
        $this->validation[]['error'] = ucfirst($value).' is required';
      }
    }

    if ( ! empty($this->validation)) {
      return false;
    }
    return true;
  }

  /**
   * Send the email
   *
   * @param array $input
   * @param array $options
   * @return bool
   */
  protected function send($input, $options) {

    $to = $options['to'];
    $subject = $options['subject'];
    $name = isset($input['name']) ? $input['name'] : 'Email Form';

    // message
    $message = "New Email\r\n";
    foreach ($input as $key => $value) {
      $message .= $key.": ".$value."\r\n";
    }

    // Additional headers
    $headers   = array();
    $headers[] = "MIME-Version: 1.0";
    $headers[] = "Content-type: text/plain; charset=iso-8859-1";
    $headers[] = "From: ".$name." <".$input['from'].">";
    $headers[] = "Reply-To: ".$name." <".$input['from'].">";
    if ($options['cc'] != '') {
      $headers[] = "Cc: ".$options['cc'];
    }
    if ($options['bcc'] != '') {
      $headers[] = "Bcc: ".$options['bcc'];
    }
    $headers[] = "Subject: ".$options['subject'];
    $headers[] = "X-Mailer: PHP/".phpversion();

    // Mail it
    return mail($options['to'], $options['subject'], $message, implode("\r\n", $headers));
  }
}