<div style="font-family: Helvetica,Arial,sans-serif;min-width:1000px;overflow:auto;line-height:2">
    <div style="margin:50px auto;width:70%;padding:20px 0">
      <div style="border-bottom:1px solid #eee">
        <a href="" style="font-size:1.4em;color: #00466a;text-decoration:none;font-weight:600"><span>{{ $app_name }} </span></a>
      </div>
      <p style="font-size:1.1em"> <center><strong>{{ $title }}</strong></center></p>
       {!! $messages !!}
      <style>
  .table {
    font-family: arial, sans-serif;
    border-collapse: collapse;
    width: 50%;
  }

  td {
    border: 1px solid #dddddd;
    text-align: left;
    padding: 8px;
  }

  tr:nth-child(even) {
    background-color: #dddddd;
  }
  </style>

     <p>Thank For choosing {{ $app_name }}</p>
      <p style="font-size:0.9em;">Regards,<br /><span>{{ $app_name }} </span></p>
      <hr style="border:none;border-top:1px solid #eee" />
      <div style="float:right;padding:8px 0;color:#aaa;font-size:0.8em;line-height:1;font-weight:300">
        <p><span>{{ $app_name }} </span> Inc</p>
        <p><span>{{ $sender_mail }} </span></p>
      </div>
    </div>
  </div>
