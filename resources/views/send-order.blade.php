<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>正在導向到藍新金流...</title>

    <style>
        body {
            padding: 1em;
            color: #0B0B61;
            text-align: center;
            width: 80%;
            margin: 0 auto;
            font-family: "微軟正黑體", "Microsoft JhengHei", "標楷體", DFKai-SB, sans-serif !important;
        }

        h1 {
            font-weight: lighter;
        }

        .loader {
            margin: 0 0 2em;
            height: 100px;
            width: 20%;
            text-align: center;
            padding: 1em;
            margin: 0 auto 1em;
            display: inline-block;
            vertical-align: top;
        }

        /*
          Set the color of the icon
        */
        svg path,
        svg rect {
            fill: #FF6700;
        }
    </style>
</head>
<body>
<form action="{{ $apiUrl }}" id="form" method="post" hidden>
    @foreach($order as $key => $val)
        <input type="hidden" name="{{ $key }}" value="{{ $val }}">
    @endforeach
</form>

<h1>正在導向到藍新金流...</h1>
<div class="loader loader--style3" title="2">
    <svg version="1.1" id="loader-1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"
         x="0px" y="0px"
         width="40px" height="40px" viewBox="0 0 50 50" style="enable-background:new 0 0 50 50;" xml:space="preserve">
  <path fill="#000"
        d="M43.935,25.145c0-10.318-8.364-18.683-18.683-18.683c-10.318,0-18.683,8.365-18.683,18.683h4.068c0-8.071,6.543-14.615,14.615-14.615c8.072,0,14.615,6.543,14.615,14.615H43.935z">
      <animateTransform attributeType="xml"
                        attributeName="transform"
                        type="rotate"
                        from="0 25 25"
                        to="360 25 25"
                        dur="0.6s"
                        repeatCount="indefinite"></animateTransform>
  </path>
  </svg>
</div>
<script>
    setTimeout(function(){
        document.getElementById('form').submit();
    }, 1000);
</script>
</body>
</html>
