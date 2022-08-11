$(function () {
    $('#upload').on('change', function (e) {
      var file = e.target.files[0];
      if (!file) {
        return false;
      }
  
      $.ajax({
        url: './signature.php',
        type: 'POST',
        data: {
          name: file.name,
          size: file.size,
          type: file.type
        },
        dataType: 'json'
      })
        .then(
          function (response) {
            // 作成した署名でブラウザからS3に直接アップロード
            var key;
            var formData = new FormData();
            for (key in response.data) {
              if (response.data.hasOwnProperty(key)) {
                formData.append(key, response.data[key]);
              }
            }
            formData.append('file', file);
  
            return $.ajax({
              url: response.upload_url,
              type: 'POST',
              data: formData,
              dataType: 'xml',
              processData: false,
              contentType: false
            });
          },
          function (error) {
            console.log('署名作成エラー');
            console.log(error);
          }
        )
        .then(
          function (response) {
            // アップロードしたCSVのURLを取得
            var url = $(response).find('Location').first().text();
            // あとはご自由に処理してください
            $('#uploads').append('');
          },
          function (error) {
            console.log('アップロードエラー');
            console.log(error);
          }
        )
  
    })
  });