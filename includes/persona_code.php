?>

<small>Powered by <b><a target="__blank" href="https://withpersona.com/">With Persona</b></a></small><br>&nbsp;<br>
<script src="https://cdn.withpersona.com/dist/persona-v4.8.0.js"></script>

<script>

  const client = new Persona.Client({

    templateId: 'itmpl_1k5CoM5gd1oo2cbxn1zdZnWZ',

    environmentId: 'env_9tVLdprhMFNx9fHSPZe7HUPM',

    onReady: () => client.open(),

    onComplete: ({ inquiryId, status, fields }) => {

      console.log(`Completed inquiry ${inquiryId} with status ${status}`);

    }

  });

</script>
<?php
