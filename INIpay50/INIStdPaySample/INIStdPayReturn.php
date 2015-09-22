<!DOCTYPE html>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <style type="text/css">
            body { background-color: #efefef;}
            body, tr, td {font-size:11pt; font-family:굴림,verdana; color:#433F37; line-height:19px;}
            table, img {border:none}

        </style>
        <link rel="stylesheet" href="../css/group.css" type="text/css">
        <script type="text/javascript">
            function cancelTid() {
                var form = document.frm;

                var win = window.open('', 'OnLine', 'scrollbars=no,status=no,toolbar=no,resizable=0,location=no,menu=no,width=600,height=400');
                win.focus();
                form.action = "http://walletpaydemo.inicis.com/stdpay/cancel/INIcancel_index.jsp";
                form.method = "post";
                form.target = "OnLine";
                form.submit();

            }
        </script>
    </head>
    <body bgcolor="#FFFFFF" text="#242424" leftmargin=0 topmargin=15 marginwidth=0 marginheight=0 bottommargin=0 rightmargin=0>
        <div style="padding:10px;width:100%;font-size:14px;color: #ffffff;background-color: #000000;text-align: center">
            이니시스 표준결제 인증결과 수신 / 승인요청, 승인결과 표시 샘플
        </div>
        <?php
        require_once('../libs/INIStdPayUtil.php');
        require_once('../libs/HttpClient.php');
        $util = new INIStdPayUtil();

        try {

            //#############################
            // 인증결과 파라미터 일괄 수신
            //#############################
//		$var = $_REQUEST["data"];
//		System.out.println("paramMap : "+ paramMap.toString());
            //#####################
            // 인증이 성공일 경우만
            //#####################
            if (strcmp("0000", $_REQUEST["resultCode"]) == 0) {

                echo "####인증성공/승인요청####";
                echo "<br/>";


                //############################################
                // 1.전문 필드 값 설정(***가맹점 개발수정***)
                //############################################

                $mid = $_REQUEST["mid"];     // 가맹점 ID 수신 받은 데이터로 설정

                $signKey = "SU5JTElURV9UUklQTEVERVNfS0VZU1RS"; // 가맹점에 제공된 키(이니라이트키) (가맹점 수정후 고정) !!!절대!! 전문 데이터로 설정금지

                $timestamp = $util->getTimestamp();   // util에 의해서 자동생성

                $charset = "UTF-8";        // 리턴형식[UTF-8,EUC-KR](가맹점 수정후 고정)

                $format = "JSON";        // 리턴형식[XML,JSON,NVP](가맹점 수정후 고정)
                // 추가적 noti가 필요한 경우(필수아님, 공백일 경우 미발송, 승인은 성공시, 실패시 모두 Noti발송됨) 미사용 
                //String notiUrl	= "";

                $authToken = $_REQUEST["authToken"];   // 취소 요청 tid에 따라서 유동적(가맹점 수정후 고정)

                $authUrl = $_REQUEST["authUrl"];    // 승인요청 API url(수신 받은 값으로 설정, 임의 세팅 금지)

                $netCancel = $_REQUEST["netCancel"];   // 망취소 API url(수신 받은f값으로 설정, 임의 세팅 금지)

                $ackUrl = $_REQUEST["checkAckUrl"];   // 가맹점 내부 로직 처리후 최종 확인 API URL(수신 받은 값으로 설정, 임의 세팅 금지)

                //#####################
                // 2.signature 생성
                //#####################
                $signParam["authToken"] = $authToken;  // 필수
                $signParam["timestamp"] = $timestamp;  // 필수
                // signature 데이터 생성 (모듈에서 자동으로 signParam을 알파벳 순으로 정렬후 NVP 방식으로 나열해 hash)
                $signature = $util->makeSignature($signParam);


                //#####################
                // 3.API 요청 전문 생성
                //#####################
                $authMap["mid"] = $mid;   // 필수
                $authMap["authToken"] = $authToken; // 필수
                $authMap["signature"] = $signature; // 필수
                $authMap["timestamp"] = $timestamp; // 필수
                $authMap["charset"] = $charset;  // default=UTF-8
                $authMap["format"] = $format;  // default=XML
                //if(null != notiUrl && notiUrl.length() > 0){
                //	authMap.put("notiUrl"		,notiUrl);
                //}




                try {

                    $httpUtil = new HttpClient();

                    //#####################
                    // 4.API 통신 시작
                    //#####################

                    $authResultString = "";
                    if ($httpUtil->processHTTP($authUrl, $authMap)) {
                        $authResultString = $httpUtil->body;
                    } else {
                        echo "Http Connect Error\n";
                        echo $httpUtil->errormsg;

                        throw new Exception("Http Connect Error");
                    }

                    //############################################################
                    //5.API 통신결과 처리(***가맹점 개발수정***)
                    //############################################################
                    echo "## 승인 API 결과 ##";

                    $resultMap = json_decode($authResultString, true);

                    echo "<pre>";
                    echo "<table width='565' border='0' cellspacing='0' cellpadding='0'>";

                    if (strcmp("0000", $resultMap["resultCode"]) == 0) {
                        /*                         * ***************************************************************************
                         * 여기에 가맹점 내부 DB에 결제 결과를 반영하는 관련 프로그램 코드를 구현한다.  

                          [중요!] 승인내용에 이상이 없음을 확인한 뒤 가맹점 DB에 해당건이 정상처리 되었음을 반영함
                          처리중 에러 발생시 망취소를 한다.
                         * **************************************************************************** */


                        /*                         * ***************************************************************************
                          내부로직 처리가 정상적으로 완료 되면 ackUrl로 결과 통신한다.
                          만약 ACK통신중 에러 발생시(exeption) 망취소를 한다.
                         * **************************************************************************** */
                        $checkMap["mid"] = $mid;        // 필수					
                        $checkMap["tid"] = $resultMap["tid"];    // 필수					
                        $checkMap["applDate"] = $resultMap["applDate"];  // 필수					
                        $checkMap["applTime"] = $resultMap["applTime"];  // 필수					
                        $checkMap["price"] = $resultMap["TotPrice"];   // 필수					
                        $checkMap["goodsName"] = $resultMap["goodsname"];  // 필수				
                        $checkMap["charset"] = $charset;  // default=UTF-8					
                        $checkMap["format"] = $format;  // default=XML		

                        $ackResultString = "";
                        if ($httpUtil->processHTTP($ackUrl, $checkMap)) {
                            $ackResultString = $httpUtil->body;
                        } else {
                            echo "Http Connect Error\n";
                            echo $httpUtil->errormsg;

                            throw new Exception("Http Connect Error");
                        }

                        $ackMap = json_decode($ackResultString);

                        echo "<tr><th class='td01'><p>거래 성공 여부</p></th>";
                        echo "<td class='td02'><p>성공</p></td></tr>";
                    } else {
                        echo "<tr><th class='td01'><p>거래 성공 여부</p></th>";
                        echo "<td class='td02'><p>실패</p></td></tr>";
                    }

                    //공통 부분만

                    echo
                    "<tr><th class='line' colspan='2'><p></p></th></tr>
					<tr><th class='td01'><p>거래 번호</p></th>
					<td class='td02'><p>" . $resultMap["tid"] . "</p></td></tr>
					<tr><th class='line' colspan='2'><p></p></th></tr>
					<tr><th class='td01'><p>결제방법(지불수단)</p></th>
					<td class='td02'><p>" . $resultMap["payMethod"] . "</p></td></tr>
					<tr><th class='line' colspan='2'><p></p></th></tr>
					<tr><th class='td01'><p>결과 코드</p></th>
					<td class='td02'><p>" . $resultMap["resultCode"] . "</p></td></tr>
					<tr><th class='line' colspan='2'><p></p></th></tr>
					<tr><th class='td01'><p>결과 내용</p></th>
					<td class='td02'><p>" . $resultMap["resultMsg"] . "</p></td></tr>	
					<tr><th class='line' colspan='2'><p></p></th></tr>
					<tr><th class='td01'><p>결제완료금액</p></th>
					<td class='td02'><p>" . $resultMap["TotPrice"] . "원</p></td></tr>
					<tr><th class='line' colspan='2'><p></p></th></tr>
					<tr><th class='td01'><p>주문 번호</p></th>
					<td class='td02'><p>" . $resultMap["orderNumber"] . "</p></td></tr>
					<tr><th class='line' colspan='2'><p></p></th></tr>
					<tr><th class='td01'><p>승인날짜</p></th>
					<td class='td02'><p>" . $resultMap["applDate"] . "</p></td></tr>
					<tr><th class='line' colspan='2'><p></p></th></tr>
					<tr><th class='td01'><p>승인시간</p></th>
					<td class='td02'><p>" . $resultMap["applTime"] . "</p></td></tr>
					<tr><th class='line' colspan='2'><p></p></th></tr>";

                    if (strcmp("VBank", $resultMap["payMethod"]) == 0) { //가상계좌
                        echo "<tr><th class='line' colspan='2'><p></p></th></tr>
					<tr><th class='td01'><p>입금 계좌번호</p></th>
					<td class='td02'><p>" . $resultMap["VACT_Num"] . "</p></td></tr>
					<tr><th class='line' colspan='2'><p></p></th></tr>
					<tr><th class='td01'><p>입금 은행코드</p></th>
					<td class='td02'><p>" . $resultMap["VACT_BankCode"] . "</p></td></tr>
					<tr><th class='line' colspan='2'><p></p></th></tr>
					<tr><th class='td01'><p>입금 은행명</p></th>
					<td class='td02'><p>" . $resultMap["vactBankName"] . "</p></td></tr>
					<tr><th class='line' colspan='2'><p></p></th></tr>
					<tr><th class='td01'><p>예금주 명</p></th>
					<td class='td02'><p>" . $resultMap["VACT_Name"] . "</p></td></tr>
					<tr><th class='line' colspan='2'><p></p></th></tr>
					<tr><th class='td01'><p>송금자 명</p></th>
					<td class='td02'><p>" . $resultMap["VACT_InputName"] . "</p></td></tr>
					<tr><th class='line' colspan='2'><p></p></th></tr>
					<tr><th class='td01'><p>송금 일자</p></th>
					<td class='td02'><p>" . $resultMap["VACT_Date"] . "</p></td></tr>
					<tr><th class='line' colspan='2'><p></p></th></tr>
					<tr><th class='td01'><p>송금 시간</p></th>
					<td class='td02'><p>" . $resultMap["VACT_Time"] . "</p></td></tr>				
					<tr><th class='line' colspan='2'><p></p></th></tr>";
                    } else if (strcmp("DirectBank", $resultMap["payMethod"]) == 0) { //실시간계좌이체
                        echo "<tr><th class='line' colspan='2'><p></p></th></tr>
					<tr><th class='td01'><p>은행코드</p></th>
					<td class='td02'><p>" . $resultMap["ACCT_BankCode"] . "</p></td></tr>
					<tr><th class='line' colspan='2'><p></p></th></tr>
					<tr><th class='td01'><p>현금영수증 발급결과코드</p></th>
					<td class='td02'><p>" . $resultMap["CSHRResultCode"] . "</p></td></tr>
					<tr><th class='line' colspan='2'><p></p></th></tr>
					<tr><th class='td01'><p>현금영수증 발급구분코드</p> <font color=red><b>(0 - 소득공제용, 1 - 지출증빙용)</b></font></th>
					<td class='td02'><p>" . $resultMap["CSHR_Type"] . "</p></td></tr>
					<tr><th class='line' colspan='2'><p></p></th></tr>";
                    } else if (strcmp("HPP", $resultMap["payMethod"]) == 0) { //휴대폰
                        echo "<tr><th class='line' colspan='2'><p></p></th></tr>
					<tr><th class='td01'><p>통신사</p></th>
					<td class='td02'><p>" . $resultMap["hppCorp"] . "</p></td></tr>
					<tr><th class='line' colspan='2'><p></p></th></tr>
					<tr><th class='td01'><p>결제장치</p></th>
					<td class='td02'><p>" . $resultMap["payDevice"] . "</p></td></tr>
					<tr><th class='line' colspan='2'><p></p></th></tr>
					<tr><th class='td01'><p>휴대폰번호</p></th>
					<td class='td02'><p>" . $resultMap["HPP_Num"] . "</p></td></tr>
					<tr><th class='line' colspan='2'><p></p></th></tr>";
                    } else if (strcmp("KWPY", $resultMap["payMethod"]) == 0) { //뱅크월렛 카카오
                        echo "<tr><th class='td01'><p>휴대폰번호</p></th>
					<td class='td02'><p>" . $resultMap["KWPY_CellPhone"] . "</p></td></tr>
					<tr><th class='line' colspan='2'><p></p></th></tr>
					<tr><th class='td01'><p>거래금액</p></th>
					<td class='td02'><p>" . $resultMap["KWPY_SalesAmount"] . "</p></td></tr>
					<tr><th class='line' colspan='2'><p></p></th></tr>
					<tr><th class='td01'><p>공급가액</p></th>
					<td class='td02'><p>" . $resultMap["KWPY_Amount"] . "</p></td></tr>
					<tr><th class='line' colspan='2'><p></p></th></tr>
					<tr><th class='td01'><p>부가세</p></th>
					<td class='td02'><p>" . $resultMap["KWPY_Tax"] . "</p></td></tr>
					<tr><th class='line' colspan='2'><p></p></th></tr>
					<tr><th class='td01'><p>봉사료</p></th>
					<td class='td02'><p>" . $resultMap["KWPY_ServiceFee"] . "</p></td></tr>
					<tr><th class='line' colspan='2'><p></p></th></tr>";
                    } else if (strcmp("DGCL", $resultMap["payMethod"]) == 0) { //게임문화상품권
                        $sum = "0";
                        $sum2 = "0";
                        $sum3 = "0";
                        $sum4 = "0";
                        $sum5 = "0";
                        $sum6 = "0";

                        echo "<tr><th class='line' colspan='2'><p></p></th></tr>
					<tr><th class='td01'><p>게임문화상품권승인금액</p></th>
					<td class='td02'><p>" . $resultMap["GAMG_ApplPrice"] . "원</p></td></tr>
					<tr><th class='line' colspan='2'><p></p></th></tr>
					<tr><th class='td01'><p>사용한 카드수</p></th>
					<td class='td02'><p>" . $resultMap["GAMG_Cnt"] . "</p></td></tr>
					<tr><th class='line' colspan='2'><p></p></th></tr>
					<tr><th class='td01'><p>사용한 카드번호</p></th>
					<td class='td02'><p>" . $resultMap["GAMG_Num1"] . "</p></td></tr>
					<tr><th class='line' colspan='2'><p></p></th></tr>
					<tr><th class='td01'><p>카드잔액</p></th>
					<td class='td02'><p>" . $resultMap["GAMG_Price1"] . "원</p></td></tr>";

                        if (!strcmp("", $resultMap["GAMG_Num2"]) == 0) {

                            echo "<tr><th class='line' colspan='2'><p></p></th></tr>
						<tr><th class='td01'><p>사용한 카드번호</p></th>
						<td class='td02'><p>" . $resultMap["GAMG_Num2"] . "</p></td></tr>
						<tr><th class='line' colspan='2'><p></p></th></tr>
						<tr><th class='td01'><p>카드잔액</p></th>
						<td class='td02'><p>" . $resultMap["GAMG_Price2"] . "원</p></td></tr>";
                        }
                        if (!strcmp("", $resultMap["GAMG_Num3"]) == 0) {

                            echo "<tr><th class='line' colspan='2'><p></p></th></tr>
						<tr><th class='td01'><p>사용한 카드번호</p></th>
						<td class='td02'><p>" . $resultMap["GAMG_Num3"] . "</p></td></tr>
						<tr><th class='line' colspan='2'><p></p></th></tr>
						<tr><th class='td01'><p>카드잔액</p></th>
						<td class='td02'><p>" . $resultMap["GAMG_Price3"] . "원</p></td></tr>";
                        }
                        if (!strcmp("", $resultMap["GAMG_Num4"]) == 0) {

                            echo "<tr><th class='line' colspan='2'><p></p></th></tr>
						<tr><th class='td01'><p>사용한 카드번호</p></th>
						<td class='td02'><p>" . $resultMap["GAMG_Num4"] . "</p></td></tr>
						<tr><th class='line' colspan='2'><p></p></th></tr>
						<tr><th class='td01'><p>카드잔액</p></th>
						<td class='td02'><p>" . $resultMap["GAMG_Price4"] . "원</p></td></tr>";
                        }
                        if (!strcmp("", $resultMap["GAMG_Num5"]) == 0) {

                            echo "<tr><th class='line' colspan='2'><p></p></th></tr>
						<tr><th class='td01'><p>사용한 카드번호</p></th>
						<td class='td02'><p>" . $resultMap["GAMG_Num5"] . "</p></td></tr>
						<tr><th class='line' colspan='2'><p></p></th></tr>
						<tr><th class='td01'><p>카드잔액</p></th>
						<td class='td02'><p>" . $resultMap["GAMG_Price5"] . "원</p></td></tr>";
                        }
                        if (!strcmp("", $resultMap["GAMG_Num6"]) == 0) {

                            echo "<tr><th class='line' colspan='2'><p></p></th></tr>
						<tr><th class='td01'><p>사용한 카드번호</p></th>
						<td class='td02'><p>" . $resultMap["GAMG_Num6"] . "</p></td></tr>
						<tr><th class='line' colspan='2'><p></p></th></tr>
						<tr><th class='td01'><p>카드잔액</p></th>
						<td class='td02'><p>" . $resultMap["GAMG_Price6"] . "원</p></td></tr>";
                        }

                        echo "<tr><th class='line' colspan='2'><p></p></th></tr>";
                    } else if (strcmp("OCBPoint", $resultMap["payMethod"]) == 0) { //오케이 캐쉬백
                        echo "<tr><th class='line' colspan='2'><p></p></th></tr>
					<tr><th class='td01'><p>지불구분</p></th>
					<td class='td02'><p>" . $resultMap["PayOption"] . "</p></td></tr>
					<tr><th class='line' colspan='2'><p></p></th></tr>
					<tr><th class='td01'><p>결제완료금액</p></th>
					<td class='td02'><p>" . $resultMap["applPrice"] . "원</p></td></tr>
					<tr><th class='line' colspan='2'><p></p></th></tr>
					<tr><th class='td01'><p>OCB 카드번호</p></th>
					<td class='td02'><p>" . $resultMap["OCB_Num"] . "</p></td></tr>
					<tr><th class='line' colspan='2'><p></p></th></tr>
					<tr><th class='td01'><p>적립 승인번호</p></th>
					<td class='td02'><p>" . $resultMap["OCB_SaveApplNum"] . "</p></td></tr>
					<tr><th class='line' colspan='2'><p></p></th></tr>
					<tr><th class='td01'><p>사용 승인번호</p></th>
					<td class='td02'><p>" . $resultMap["OCB_PayApplNum"] . "</p></td></tr>
					<tr><th class='line' colspan='2'><p></p></th></tr>					
					<tr><th class='td01'><p>OCB 지불 금액</p></th>
					<td class='td02'><p>" . $resultMap["OCB_PayPrice"] . "</p></td></tr>
					<tr><th class='line' colspan='2'><p></p></th></tr>";
                    } else if (strcmp("GSPT", $resultMap["payMethod"]) == 0) { //GSPoint
                        echo "<tr><th class='line' colspan='2'><p></p></th></tr>
					<tr><th class='td01'><p>지불구분</p></th>
					<td class='td02'><p>" . $resultMap["PayOption"] . "</p></td></tr>					
					<tr><th class='line' colspan='2'><p></p></th></tr>
					<tr><th class='td01'><p>GS 포인트 승인금액</p></th>
					<td class='td02'><p>" . $resultMap["GSPT_ApplPrice"] . "원</p></td></tr>
					<tr><th class='line' colspan='2'><p></p></th></tr>
					<tr><th class='td01'><p>GS 포인트 적립금액</p></th>
					<td class='td02'><p>" . $resultMap["GSPT_SavePrice"] . "원</p></td></tr>					
					<tr><th class='line' colspan='2'><p></p></th></tr>
					<tr><th class='td01'><p>GS 포인트 지불금액</p></th>
					<td class='td02'><p>" . $resultMap["GSPT_PayPrice"] . "원</p></td></tr>
					<tr><th class='line' colspan='2'><p></p></th></tr>";
                    } else if (strcmp("UPNT", $resultMap["payMethod"]) == 0) {  //U-포인트
                        echo "<tr><th class='line' colspan='2'><p></p></th></tr>
					<tr><th class='td01'><p>U포인트 카드번호</p></th>
					<td class='td02'><p>" . $resultMap["UPoint_Num"] . "</p></td></tr>
					<tr><th class='line' colspan='2'><p></p></th></tr>
					<tr><th class='td01'><p>가용포인트</p></th>
					<td class='td02'><p>" . $resultMap["UPoint_usablePoint"] . "</p></td></tr>
					<tr><th class='line' colspan='2'><p></p></th></tr>			
					<tr><th class='td01'><p>포인트지불금액</p></th>
					<td class='td02'><p>" . $resultMap["UPoint_ApplPrice"] . "</p></td></tr>
					<tr><th class='line' colspan='2'><p></p></th></tr>";
                    } else if (strcmp("KWPY", $resultMap["payMethod"]) == 0) {  //뱅크월렛 카카오
                        echo "<tr><th class='td01'><p>결제방법</p></th>
					<td class='td02'><p>" . $resultMap["payMethod"] . "</p></td></tr>
					<tr><th class='line' colspan='2'><p></p></th></tr>
					<tr><th class='td01'><p>결과 코드</p></th>
					<td class='td02'><p>" . $resultMap["resultCode"] . "</p></td></tr>
					<tr><th class='line' colspan='2'><p></p></th></tr>
					<tr><th class='td01'><p>결과 내용</p></th>
					<td class='td02'><p>" . $resultMap["resultMsg"] . "</p></td></tr>
					<tr><th class='line' colspan='2'><p></p></th></tr>
					<tr><th class='td01'><p>거래 번호</p></th>
					<td class='td02'><p>" . $resultMap["tid"] . "</p></td></tr>
					<tr><th class='line' colspan='2'><p></p></th></tr>
					<tr><th class='td01'><p>주문 번호</p></th>
					<td class='td02'><p>" . $resultMap["orderNumber"] . "</p></td></tr>
					<tr><th class='line' colspan='2'><p></p></th></tr>
					<tr><th class='td01'><p>결제완료금액</p></th>
					<td class='td02'><p>" . $resultMap["price"] . "원</p></td></tr>
					<tr><th class='line' colspan='2'><p></p></th></tr>
					<tr><th class='td01'><p>사용일자</p></th>
					<td class='td02'><p>" . $resultMap["applDate"] . "</p></td></tr>
					<tr><th class='line' colspan='2'><p></p></th></tr>
					<tr><th class='td01'><p>사용시간</p></th>
					<td class='td02'><p>" . $resultMap["applTime"] . "</p></td></tr>
					<tr><th class='line' colspan='2'><p></p></th></tr>";
                    } else if (strcmp("YPAY", $resultMap["payMethod"]) == 0) { //엘로우 페이
                        //별도 응답 필드 없음
                    } else if (strcmp("TEEN", $resultMap["payMethod"]) == 0) { //틴캐시
                        echo "<tr><th class='line' colspan='2'><p></p></th></tr>
					<tr><th class='td01'><p>틴캐시 승인번호</p></th>
					<td class='td02'><p>" . $resultMap["TEEN_ApplNum"] . "</p></td></tr>									
					<tr><th class='line' colspan='2'><p></p></th></tr>
					<tr><th class='td01'><p>틴캐시아이디</p></th>
					<td class='td02'><p>" . $resultMap["TEEN_UserID"] . "</p></td></tr>
					<tr><th class='line' colspan='2'><p></p></th></tr>
					<tr><th class='line' colspan='2'><p></p></th></tr>
					<tr><th class='td01'><p>틴캐시승인금액</p></th>
					<td class='td02'><p>" . $resultMap["TEEN_ApplPrice"] . "원</p></td></tr>";
                    } else if (strcmp("Bookcash", $resultMap["payMethod"]) == 0) { //도서문화상품권
                        echo "<tr><th class='line' colspan='2'><p></p></th></tr>
					<tr><th class='td01'><p>도서상품권 승인번호</p></th>
					<td class='td02'><p>" . $resultMap["BCSH_ApplNum"] . "</p></td></tr>
					<tr><th class='line' colspan='2'><p></p></th></tr>
					<tr><th class='td01'><p>도서상품권 사용자ID</p></th>
					<td class='td02'><p>" . $resultMap["BCSH_UserID"] . "</p></td></tr>
					<tr><th class='line' colspan='2'><p></p></th></tr>
					<tr><th class='td01'><p>도서상품권 승인금액</p></th>
					<td class='td02'><p>" . $resultMap["BCSH_ApplPrice"] . "원</p></td></tr>";
                    } else if (strcmp("PhoneBill", $resultMap["payMethod"]) == 0) { //폰빌전화결제
                        echo "<tr><th class='line' colspan='2'><p></p></th></tr>
					<tr><th class='td01'><p>승인전화번호</p></th>
					<td class='td02'><p>" . $resultMap["PHNB_Num"] . "</p></td></tr>
					<tr><th class='line' colspan='2'><p></p></th></tr>";
                    } else if (strcmp("Bill", $resultMap["payMethod"]) == 0) { //빌링결제
                        echo "<tr><th class='line' colspan='2'><p></p></th></tr>
					<tr><th class='td01'><p>빌링키</p></th>
					<td class='td02'><p>" . $resultMap["CARD_BillKey"] . "</p></td></tr>";
                    } else { //카드
//					int  quota=Integer.parseInt(resultMap.get("CARD_Quota"));
                        if (!is_null($resultMap["EventCode"])) {

                            echo "<tr><th class='line' colspan='2'><p></p></th></tr>
						<tr><th class='td01'><p>이벤트 코드</p></th>					
						<td class='td02'><p>" . $resultMap["EventCode"] . "</p></td></tr>";
                        }

                        echo "<tr><th class='line' colspan='2'><p></p></th></tr>
					<tr><th class='td01'><p>카드번호</p></th>
					<td class='td02'><p>" . $resultMap["CARD_Num"] . "</p></td></tr>
					<tr><th class='line' colspan='2'><p></p></th></tr>
					<tr><th class='td01'><p>할부기간</p></th>
					<td class='td02'><p>" . $resultMap["CARD_Quota"] . "</p></td></tr>
					<tr><th class='line' colspan='2'><p></p></th></tr>";

                        if (strcmp("1", $resultMap["CARD_Interest"]) == 0 || strcmp("1", $resultMap["EventCode"]) == 0) {

                            echo "<tr><th class='td01'><p>할부 유형</p></th>
						<td class='td02'><p>무이자</p></td></tr>";
                        } else if ($quota > 0 && !strcmp("1", $resultMap["CARD_Interest"]) == 0) {

                            echo "<tr><th class='td01'><p>할부 유형</p></th>
						<td class='td02'><p>유이자 <font color='red'> *유이자로 표시되더라도 EventCode 및 EDI에 따라 무이자 처리가 될 수 있습니다.</font></p></td></tr>";
                        }

                        if (strcmp("1", $resultMap["point"]) == 0) {

                            echo "<td class='td02'><p></p></td></tr>
						<tr><th class='td01'><p>포인트 사용 여부</p></th>
						<td class='td02'><p>사용</p></td></tr>";
                        } else {

                            echo "<td class='td02'><p></p></td></tr>
						<tr><th class='td01'><p>포인트 사용 여부</p></th>
						<td class='td02'><p>미사용</p></td></tr>";
                        }

                        echo "<tr><th class='line' colspan='2'><p></p></th></tr>
					<tr><th class='td01'><p>카드 종류</p></th>
					<td class='td02'><p>" . $resultMap["cardCode"] . "</p></td></tr>
					<tr><th class='line' colspan='2'><p></p></th></tr>
					<tr><th class='td01'><p>카드 발급사</p></th>
					<td class='td02'><p>" . $resultMap["cardCode"] . "</p></td></tr>";

                        if (!is_null($resultMap["OCB_Num"]) && !empty($resultMap["OCB_Num"])) {

                            echo "<tr><th class='line' colspan='2'><p></p></th></tr>
						<tr><th class='td01'><p>OK CASHBAG 카드번호</p></th>
						<td class='td02'><p>" . $resultMap["OCB_Num"] . "</p></td></tr>
						<tr><th class='line' colspan='2'><p></p></th></tr>
						<tr><th class='td01'><p>OK CASHBAG 적립 승인번호</p></th>
						<td class='td02'><p>" . $resultMap["OCB_SaveApplNum"] . "</p></td></tr>
						<tr><th class='line' colspan='2'><p></p></th></tr>
						<tr><th class='td01'><p>OK CASHBAG 포인트지불금액</p></th>
						<td class='td02'><p>" . $resultMap["OCB_PayPrice"] . "</p></td></tr>";
                        }
                        if (!is_null($resultMap["GSPT_Num"]) && !empty($resultMap["GSPT_Num"])) {

                            echo "<tr><th class='line' colspan='2'><p></p></th></tr>
						<tr><th class='td01'><p>GS&Point 카드번호</p></th>
						<td class='td02'><p>" . $resultMap["GSPT_Num"] . "</p></td></tr>

						<tr><th class='line' colspan='2'><p></p></th></tr>
						<tr><th class='td01'><p>GS&Point 잔여한도</p></th>
						<td class='td02'><p>" . $resultMap["GSPT_Remains"] . "</p></td></tr>
						
						<tr><th class='line' colspan='2'><p></p></th></tr>
						<tr><th class='td01'><p>GS&Point 승인금액</p></th>
						<td class='td02'><p>" . $resultMap["GSPT_ApplPrice"] . "</p></td></tr>";
                        }

                        if (!is_null($resultMap["UNPT_CardNum"]) && !empty($resultMap["UNPT_CardNum"])) {

                            echo "<tr><th class='line' colspan='2'><p></p></th></tr>
						<tr><th class='td01'><p>U-Point 카드번호</p></th>
						<td class='td02'><p>" . $resultMap["UNPT_CardNum"] . "</p></td></tr>
						
						<tr><th class='line' colspan='2'><p></p></th></tr>
						<tr><th class='td01'><p>U-Point 가용포인트</p></th>
						<td class='td02'><p>" . $resultMap["UPNT_UsablePoint"] . "</p></td></tr>
						
						<tr><th class='line' colspan='2'><p></p></th></tr>
						<tr><th class='td01'><p>U-Point 포인트지불금액</p></th>
						<td class='td02'><p>" . $resultMap["UPNT_PayPrice"] . "</p></td></tr>";
                        }
                    }

                    echo "</table>
				<span style='padding-left : 100px;'>
				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<!--input type='button' value='거래취소' onclick='cancelTid()' style='width : 50px ; height : 40px; font-size= 10pt; margin : 0 auto;' /-->
				</span>
				<form name='frm' method='post'> 
				<input type='hidden' name='tid' value='" . $resultMap["tid"] . "'/>
				</form>				
				</pre>";

                    // 수신결과를 파싱후 resultCode가 "0000"이면 승인성공 이외 실패
                    // 가맹점에서 스스로 파싱후 내부 DB 처리 후 화면에 결과 표시
                    // payViewType을 popup으로 해서 결제를 하셨을 경우
                    // 내부처리후 스크립트를 이용해 opener의 화면 전환처리를 하세요
                    //throw new Exception("강제 Exception");
                } catch (Exception $e) {
                    //    $s = $e->getMessage() . ' (오류코드:' . $e->getCode() . ')';
                    //####################################
                    // 실패시 처리(***가맹점 개발수정***)
                    //####################################
                    //---- db 저장 실패시 등 예외처리----//
                    $s = $e->getMessage() . ' (오류코드:' . $e->getCode() . ')';
                    echo $s;

                    //#####################
                    // 망취소 API
                    //#####################

                    $netcancelResultString = ""; // 망취소 요청 API url(고정, 임의 세팅 금지)
                    if ($httpUtil->processHTTP($netCancel, $authMap)) {
                        $netcancelResultString = $httpUtil->body;
                    } else {
                        echo "Http Connect Error\n";
                        echo $httpUtil->errormsg;

                        throw new Exception("Http Connect Error");
                    }

                    echo "## 망취소 API 결과 ##";

                    $netcancelResultString = str_replace("<", "&lt;", $$netcancelResultString);
                    $netcancelResultString = str_replace(">", "&gt;", $$netcancelResultString);

                    echo "<pre>", $netcancelResultString . "</pre>";
                    // 취소 결과 확인
                }
            } else {

                //#############
                // 인증 실패시
                //#############
                echo "<br/>";
                echo "####인증실패####";

                echo "<pre>" . var_dump($_REQUEST) . "</pre>";
            }
        } catch (Exception $e) {
            $s = $e->getMessage() . ' (오류코드:' . $e->getCode() . ')';
            echo $s;
        }
        ?>
